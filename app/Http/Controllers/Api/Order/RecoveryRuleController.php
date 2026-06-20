<?php

namespace App\Http\Controllers\Api\Order;

use App\Domain\Orders\Models\RecoveryRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

final class RecoveryRuleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->user()->team_id;
        $rules = RecoveryRule::query()
            ->where(fn ($query) => $query->where('team_id', $teamId)->orWhereNull('team_id'))
            ->where('action', 'send_whatsapp')
            ->orderBy('delay_minutes')
            ->get();

        return response()->json([
            'data' => $rules->isEmpty()
                ? $this->defaultStages()
                : $rules->map(fn (RecoveryRule $rule) => $this->mapRule($rule))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'stages' => ['required', 'array'],
            'stages.*.id' => ['nullable', 'integer'],
            'stages.*.name' => ['required', 'string', 'max:255'],
            'stages.*.enabled' => ['required', 'boolean'],
            'stages.*.delay_minutes' => ['required', 'integer', 'min:1'],
            'stages.*.language' => ['required', 'string', 'in:fr,en,ar'],
            'stages.*.template_name' => ['nullable', 'string', 'max:255'],
            'stages.*.body_params' => ['array'],
            'stages.*.body_params.*' => ['nullable', 'string', 'max:255'],
            'stages.*.url_suffix' => ['nullable', 'string', 'max:255'],
        ]);

        $teamId = $request->user()->team_id;

        DB::transaction(function () use ($validated, $teamId) {
            $keptIds = [];
            foreach ($validated['stages'] as $index => $stage) {
                $rule = isset($stage['id'])
                    ? RecoveryRule::query()
                        ->where('team_id', $teamId)
                        ->whereKey($stage['id'])
                        ->first()
                    : null;

                $rule = $rule ?: new RecoveryRule(['team_id' => $teamId]);
                $this->fillRule($rule, $stage, $index + 1);
                $rule->save();
                $keptIds[] = $rule->id;
            }

            RecoveryRule::query()
                ->where('team_id', $teamId)
                ->where('action', 'send_whatsapp')
                ->when(! empty($keptIds), fn ($query) => $query->whereNotIn('id', $keptIds))
                ->delete();
        });

        return $this->index($request);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'enabled' => ['sometimes', 'boolean'],
            'delay_minutes' => ['sometimes', 'integer', 'min:1'],
            'language' => ['sometimes', 'string', 'in:fr,en,ar'],
            'template_name' => ['nullable', 'string', 'max:255'],
            'body_params' => ['sometimes', 'array'],
            'body_params.*' => ['nullable', 'string', 'max:255'],
            'url_suffix' => ['nullable', 'string', 'max:255'],
        ]);

        $rule = RecoveryRule::query()
            ->where('team_id', $request->user()->team_id)
            ->findOrFail($id);

        $stage = array_merge($this->mapRule($rule), $validated);
        $this->fillRule($rule, $stage, (int) ($stage['stage_index'] ?? 1));
        $rule->save();

        return response()->json(['data' => $this->mapRule($rule)]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        RecoveryRule::query()
            ->where('team_id', $request->user()->team_id)
            ->findOrFail($id)
            ->delete();

        return response()->json(['deleted' => true]);
    }

    private function fillRule(RecoveryRule $rule, array $stage, int $stageIndex): void
    {
        $rule->fill([
            'name' => $stage['name'],
            'action' => 'send_whatsapp',
            'delay_minutes' => (int) $stage['delay_minutes'],
            'message_template' => $stage['template_name'] ?? 'abandoned_recovery_v1',
            'is_active' => (bool) $stage['enabled'],
            'trigger_condition' => [
                'status' => 'abandoned',
                'stage_index' => $stageIndex,
                'language' => $stage['language'] ?? 'fr',
                'body_params' => array_values(array_filter($stage['body_params'] ?? [], fn ($value) => $value !== null && $value !== '')),
                'url_suffix' => $stage['url_suffix'] ?? '',
            ],
        ]);
    }

    private function mapRule(RecoveryRule $rule): array
    {
        $config = $rule->trigger_condition ?? [];

        return [
            'id' => $rule->id,
            'name' => $rule->name,
            'enabled' => (bool) $rule->is_active,
            'delay_minutes' => (int) $rule->delay_minutes,
            'language' => $config['language'] ?? 'fr',
            'template_name' => $rule->message_template ?? 'abandoned_recovery_v1',
            'body_params' => $config['body_params'] ?? ['{{customer_name}}', '{{recovery_url}}'],
            'url_suffix' => $config['url_suffix'] ?? '',
            'stage_index' => $config['stage_index'] ?? null,
        ];
    }

    private function defaultStages(): array
    {
        return [
            $this->defaultStage('Stage 1', 60),
            $this->defaultStage('Stage 2', 1440),
            $this->defaultStage('Stage 3', 4320),
        ];
    }

    private function defaultStage(string $name, int $delay): array
    {
        return [
            'id' => null,
            'name' => $name,
            'enabled' => false,
            'delay_minutes' => $delay,
            'language' => 'fr',
            'template_name' => 'abandoned_recovery_v1',
            'body_params' => ['{{customer_name}}', '{{recovery_url}}'],
            'url_suffix' => '',
        ];
    }
}
