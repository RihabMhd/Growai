<?php

namespace App\Application\Orders\CreateOrder;

use App\Domain\Orders\Actions\OrderNumberGenerator;
use App\Domain\Orders\Actions\ProductPriceResolver;
use App\Domain\Orders\Events\OrderCreated;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderItem;
use App\Application\Dispatch\DispatchOrder\DispatchOrderCommand;
use App\Application\Dispatch\DispatchOrder\DispatchOrderHandler;
use App\Domain\Orders\Services\OrderAuditLogger;
use App\Domain\Shopify\Models\Shop;
use App\Domain\Orders\Repositories\ClientRepositoryInterface;
use App\Infrastructure\Orders\Repositories\OrderRepositoryInterface;
use App\Infrastructure\Orders\Repositories\OrderSourceRepositoryInterface;
use App\Infrastructure\Orders\Repositories\ShipmentRepositoryInterface;
use App\Domain\Products\Models\Product;
use Illuminate\Support\Facades\DB;

class CreateOrderHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface       $orders,
        private readonly ClientRepositoryInterface      $clients,
        private readonly ShipmentRepositoryInterface    $shipments,
        private readonly OrderSourceRepositoryInterface $sources,
        private readonly OrderAuditLogger               $auditLogger,
        private readonly OrderNumberGenerator           $numberGenerator,
        private readonly ProductPriceResolver           $priceResolver,
        private readonly DispatchOrderHandler           $dispatcher,
    ) {}

    public function handle(CreateOrderCommand $command): Order
    {
        // upsert client outside transaction because it is read-heavy and idempotent
        $client = $this->clients->upsertByPhone(
            phone: $command->customerPhone,
            name: $command->customerName,
            email: $command->customerEmail,
            city: $command->city,
            province: $command->province,
            street: $command->street,
        );

        // resolve shop and order number before opening transaction to avoid locking issues
        $shop          = Shop::first();
        $orderNumber   = $this->numberGenerator->generate();

        // persist order data atomically
        $order = DB::transaction(function () use ($command, $client, $shop, $orderNumber) {

            $order = $this->orders->create([
                'shop_id'          => $shop?->id,
                'client_id'        => $client->id,
                'order_number'     => $orderNumber,
                'total_price'      => 0.00,
                'shipping_price'   => $command->shippingPrice,
                'discount'         => 0.00,
                'currency'         => 'MAD',
                'status'           => 'new',
                'financial_status' => 'unpaid',
                'notes'            => $command->notes,
                'source_channel'   => $command->source,
                'is_abandoned'     => $command->isAbandoned,
                'abandoned_at'     => $command->isAbandoned ? now() : null,
                'created_at'       => now(),
            ]);

            $subtotal = 0.00;

            foreach ($command->items as $itemData) {
                $product   = Product::findOrFail($itemData['product_id']);
                $qty       = (int) $itemData['quantity'];
                $unitPrice = $this->priceResolver->resolve($product);
                $lineTotal = $unitPrice * $qty;
                $subtotal += $lineTotal;

                OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => $product->id,
                    'product_name' => $product->title,
                    'quantity'     => $qty,
                    'unit_price'   => $unitPrice,
                    'total_price'  => $lineTotal,
                ]);
            }

            $order->update(['total_price' => $subtotal + $command->shippingPrice]);

            $this->sources->recordForOrder($order, $command->source);

            if ($command->city || $command->province || $command->street) {
                $this->shipments->createForOrder($order, [
                    'delivery_company_id' => null,
                    'status'              => 'label_created',
                    'recipient_name'      => $command->customerName,
                    'recipient_phone'     => $command->customerPhone,
                    'address'             => implode(', ', array_filter([
                        $command->street,
                        $command->city,
                        $command->province,
                    ])),
                    'city'                => $command->city,
                    'region'              => $command->province,
                    'country'             => 'MA',
                    'cod_amount'          => $subtotal + $command->shippingPrice,
                ]);
            }

            $this->auditLogger->log(
                order: $order,
                userId: $command->createdByUserId,
                actionType: 'status_changed',
                oldValue: null,
                newValue: 'new',
                description: 'Commande créée manuellement.',
            );

            return $order;
        });

        // auto-dispatch outside transaction so failures do not roll back the order

        $agent = $this->dispatcher->handle(new DispatchOrderCommand($order->id));

        if ($agent) {
            $order->assigned_to = $agent->id;
            $order->save();

            $this->auditLogger->log(
                order: $order,
                userId: $agent->id,
                actionType: 'assigned',
                oldValue: 'unassigned',
                newValue: $agent->name,
                description: "Commande assignée automatiquement à l'agent {$agent->name} (Auto-Dispatch Round-Robin).",
            );
        }

        OrderCreated::dispatch($order);

        return $order->load(['items.product', 'client', 'assignedAgent']);
    }
}
