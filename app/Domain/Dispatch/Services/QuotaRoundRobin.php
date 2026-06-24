<?php

namespace App\Domain\Dispatch\Services;

use App\Domain\Teams\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Domain\Teams\Models\Team;

// persistent quota-based round-robin dispatch
// sequence rebuilds dynamically so quota changes apply immediately
final class QuotaRoundRobin
{
    public function select(Collection $agents): ?User
    {
        if ($agents->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($agents) {

            $team = Team::query()
                ->lockForUpdate()
                ->firstOrFail();

            $sequence = $this->buildSequence($agents);

            if (empty($sequence)) {
                return null;
            }

            $hash = $this->generateHash($agents);

            if ($team->dispatch_hash !== $hash) {

                $team->dispatch_hash = $hash;

                $team->save();
            }

            $index = $team->dispatch_cursor % count($sequence);

            $selected = $sequence[$index];

            $team->increment('dispatch_cursor');

            return $selected;
        });
    }

    private function buildSequence(Collection $agents): array
    {
        $sequence = [];

        foreach ($agents->sortBy('id') as $agent) {

            if ($agent->quota <= 0) {
                continue;
            }

            for ($i = 0; $i < $agent->quota; $i++) {

                $sequence[] = $agent;
            }
        }

        return $sequence;
    }

    private function generateHash(Collection $agents): string
    {
        return md5(

            $agents
                ->sortBy('id')
                ->map(

                    fn($agent) => "{$agent->id}:{$agent->quota}"
                )
                ->implode('|')
        );
    }

    private function resolveAgent(Collection $sorted, int $position): ?User
    {
        $offset = 0;

        foreach ($sorted as $agent) {
            $offset += $agent->quota;
            if ($position < $offset) {
                return $agent;
            }
        }

        // fallback if position is out of bounds
        return $sorted->first();
    }
}
