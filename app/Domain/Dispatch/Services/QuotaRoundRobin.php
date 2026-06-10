<?php

namespace App\Domain\Dispatch\Services;

use App\Domain\Teams\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Domain\Teams\Models\Team;

/**
 * Persistent quota-based round-robin using a single team-level cursor.
 *
 * Algorithm:
 *   1. Sort eligible agents by id (stable, deterministic order).
 *   2. Build the expanded slot sequence: [A,A,A,A,A,B,B,B,C,C]
 *      sequence_length = sum of quotas.
 *   3. position = cursor % sequence_length
 *   4. Walk the sequence to find which agent owns `position`.
 *   5. Increment cursor and persist.
 *
 * Quota/roster changes take effect on the next dispatch naturally:
 * the sequence is rebuilt from live data each time. The cursor keeps
 * incrementing; mod arithmetic absorbs the change without a hard reset.
 */
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

    // -------------------------------------------------------------------------

    /**
     * Walk the sorted agent list, consuming quota-sized slots, until
     * the slot at $position is owned by an agent.
     *
     * Example: agents=[A(q=5), B(q=3), C(q=2)], position=6
     *   offset=0: A owns slots 0-4, 6 >= 5 → offset=5
     *   offset=5: B owns slots 5-7, 6 < 8  → return B
     */
    private function resolveAgent(Collection $sorted, int $position): ?User
    {
        $offset = 0;

        foreach ($sorted as $agent) {
            $offset += $agent->quota;
            if ($position < $offset) {
                return $agent;
            }
        }

        // Unreachable if position < sequenceLength, but defensive fallback.
        return $sorted->first();
    }
}
