<?php

namespace App\Console\Commands;

use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Services\OrderAuditLogger;
use App\Domain\Orders\States\OrderStateMachine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MarkAbandonedOrdersCommand extends Command
{
    protected $signature = 'orders:mark-abandoned {--hours=24 : Hours since last update before no-answer orders are abandoned}';

    protected $description = 'Mark stale no-answer orders as abandoned.';

    public function __construct(
        private readonly OrderAuditLogger $auditLogger,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $cutoff = now()->subHours((int) $this->option('hours'));
        $marked = 0;

        Order::query()
            ->where('status', 'no_answer')
            ->where('is_abandoned', false)
            ->where('updated_at', '<=', $cutoff)
            ->orderBy('id')
            ->chunkById(100, function ($orders) use (&$marked) {
                foreach ($orders as $candidate) {
                    $changed = DB::transaction(function () use ($candidate): bool {
                        $order = Order::query()
                            ->whereKey($candidate->id)
                            ->lockForUpdate()
                            ->first();

                        if (
                            $order === null
                            || $order->status !== 'no_answer'
                            || $order->is_abandoned
                        ) {
                            return false;
                        }

                        (new OrderStateMachine($order))->transitionTo('abandoned');

                        $order->update([
                            'status' => $order->status,
                            'is_abandoned' => true,
                            'abandoned_at' => now(),
                        ]);

                        $this->auditLogger->log(
                            order: $order,
                            userId: null,
                            actionType: 'status_changed',
                            oldValue: 'no_answer',
                            newValue: 'abandoned',
                            description: "Commande marquÃ©e comme abandonnÃ©e aprÃ¨s 24 heures sans rÃ©ponse.",
                        );

                        return true;
                    });

                    if ($changed) {
                        $marked++;
                    }
                }
            });

        $this->info("Marked {$marked} abandoned orders.");

        return self::SUCCESS;
    }
}
