<?php

namespace App\Infrastructure\Team;

use App\Domain\Teams\TeamRepositoryInterface;
use App\Domain\Teams\Models\Team;
use App\Domain\Teams\Models\User;

class EloquentTeamRepository implements TeamRepositoryInterface
{
    public function getOrCreateDefault(): Team
    {
        return Team::firstOrCreate([], [
            'dispatch_auto'      => false,
            'inactive_strategy'  => 'do_nothing',
            'commission_currency' => 'DZ DA',
        ]);
    }

    public function save(Team $team): void
    {
        $team->save();
    }

    public function first(): ?Team
    {
        return Team::first();
    }
}
