<?php

namespace App\Domain\Dispatch\Services;

use App\Domain\Teams\Models\User;
use Illuminate\Support\Collection;

final class QuotaRoundRobin
{
    /**
     * Select the best agent from eligible candidates.
     *
     * Prefers agents under quota. Among those, picks the one with the
     * lowest assignment/quota ratio. Ties broken by absolute count (fewest wins).
     *
     * @param Collection $eligible        Collection of User models
     * @param array      $agentOrderCounts ['agent_id' => count] map
     */
    public function select(Collection $eligible, array $agentOrderCounts): ?User
    {
        $underQuota = $eligible->filter(
            fn ($agent) => ($agentOrderCounts[$agent->id] ?? 0) < $agent->quota
        );

        $candidates = $underQuota->isNotEmpty() ? $underQuota : $eligible;

        return $this->selectByLowestRatio($candidates, $agentOrderCounts);
    }

    private function selectByLowestRatio(Collection $candidates, array $agentOrderCounts): ?User
    {
        $selectedAgent = null;
        $lowestRatio   = null;

        foreach ($candidates as $agent) {
            $currentCount = $agentOrderCounts[$agent->id] ?? 0;
            $ratio        = $currentCount / $agent->quota;

            if ($selectedAgent === null || $ratio < $lowestRatio) {
                $selectedAgent = $agent;
                $lowestRatio   = $ratio;
            } elseif ($ratio == $lowestRatio) {
                $currentSelectedCount = $agentOrderCounts[$selectedAgent->id] ?? 0;
                if ($currentCount < $currentSelectedCount) {
                    $selectedAgent = $agent;
                }
            }
        }

        return $selectedAgent;
    }
}