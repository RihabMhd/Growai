<?php

namespace App\Domain\Orders\Services;

use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderHistory;
use App\Domain\Orders\Models\RecoveryRule;
use App\Domain\WhatsApp\Models\Message;
use App\Infrastructure\WhatsApp\WhatsAppServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class RecoveryRuleExecutor
{
    public function __construct(
        private readonly WhatsAppServiceInterface $whatsApp,
        private readonly RecoveryPlaceholderRenderer $renderer,
    ) {}

    /**
     * @return array{rules:int, eligible:int, sent:int, skipped:int, failed:int}
     */
    public function process(): array
    {
        $summary = ['rules' => 0, 'eligible' => 0, 'sent' => 0, 'skipped' => 0, 'failed' => 0];

        RecoveryRule::query()
            ->where('action', 'send_whatsapp')
            ->where('is_active', true)
            ->orderBy('delay_minutes')
            ->each(function (RecoveryRule $rule) use (&$summary) {
                $summary['rules']++;

                $this->eligibleOrders($rule)->chunkById(100, function ($orders) use ($rule, &$summary) {
                    foreach ($orders as $order) {
                        $summary['eligible']++;

                        if ($this->alreadyExecuted($order, $rule)) {
                            $summary['skipped']++;
                            continue;
                        }

                        $this->send($order, $rule) ? $summary['sent']++ : $summary['failed']++;
                    }
                });
            });

        return $summary;
    }

    private function eligibleOrders(RecoveryRule $rule): Builder
    {
        return Order::query()
            ->with(['client', 'shop'])
            ->where('status', 'abandoned')
            ->where('is_abandoned', true)
            ->whereNotNull('abandoned_at')
            ->where('abandoned_at', '<=', now()->subMinutes((int) $rule->delay_minutes))
            ->whereHas('client', fn (Builder $client) => $client
                ->whereNotNull('phone')
                ->where('phone', '<>', ''))
            ->when($rule->team_id, fn (Builder $orders) => $orders
                ->whereHas('shop', fn (Builder $shop) => $shop->where('team_id', $rule->team_id)));
    }

    private function alreadyExecuted(Order $order, RecoveryRule $rule): bool
    {
        return OrderHistory::query()
            ->where('order_id', $order->id)
            ->where('action_type', 'recovery_stage_executed')
            ->where('new_value', $this->stageKey($rule))
            ->exists();
    }

    private function send(Order $order, RecoveryRule $rule): bool
    {
        $phone = $order->client?->phone;
        if (! $phone) {
            return false;
        }

        $body = $this->messageBody($order, $rule);

        $message = Message::create([
            'client_id' => $order->client_id,
            'order_id' => $order->id,
            'user_id' => null,
            'channel' => 'whatsapp',
            'direction' => 'outgoing',
            'message' => $body,
            'status' => 'pending',
            'sent_at' => null,
        ]);

        try {
            $this->whatsApp->send($phone, $body);

            DB::transaction(function () use ($message, $order, $rule) {
                $message->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);

                OrderHistory::create([
                    'order_id' => $order->id,
                    'user_id' => null,
                    'action_type' => 'recovery_stage_executed',
                    'old_value' => null,
                    'new_value' => $this->stageKey($rule),
                    'description' => "Recovery stage {$rule->name} sent.",
                ]);
            });

            return true;
        } catch (\Throwable $e) {
            $message->update(['status' => 'failed']);

            Log::error('Recovery rule WhatsApp send failed', [
                'order_id' => $order->id,
                'rule_id' => $rule->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function messageBody(Order $order, RecoveryRule $rule): string
    {
        $config = $rule->trigger_condition ?? [];
        $params = $this->renderer->renderMany($config['body_params'] ?? [], $order);
        $urlSuffix = $this->renderer->render((string) ($config['url_suffix'] ?? ''), $order);
        $template = $rule->message_template ?: 'abandoned_recovery_v1';

        $lines = ["Template: {$template}"];
        foreach ($params as $index => $param) {
            $lines[] = '{{' . ($index + 1) . "}} {$param}";
        }
        if ($urlSuffix !== '') {
            $lines[] = "URL: {$urlSuffix}";
        }

        return implode("\n", $lines);
    }

    private function stageKey(RecoveryRule $rule): string
    {
        return "recovery_rule:{$rule->id}";
    }
}
