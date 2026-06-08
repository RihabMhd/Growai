<?php
namespace App\Infrastructure\Teams;

use App\Domain\Teams\TeamRepositoryInterface;
use App\Domain\Teams\Models\Team;

class EloquentTeamRepository implements TeamRepositoryInterface
{
    public function first(): ?Team
    {
        return Team::first();
    }

    public function firstOrCreate(): Team
    {
        return Team::firstOrCreate([], [
            'dispatch_auto'       => false,
            'inactive_strategy'   => 'do_nothing',
            'commission_currency' => 'DZ DA',
        ]);
    }

    public function save(object $team): void
    {
        $team->save();
    }
}