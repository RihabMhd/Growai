<?php

namespace App\Application\Teams\ListTeamMembers;

use App\Domain\Teams\TeamRepositoryInterface;
use App\Domain\Teams\Models\User;
use App\Domain\Products\Models\Product;
use App\Domain\Teams\Models\TeamMember;

class ListTeamMembersHandler
{
    public function __construct(private TeamRepositoryInterface $teams) {}

    public function handle(): array
    {
        $team    = $this->teams->getOrCreateDefault();
        $members = User::where('team_id', $team->id)
               ->with('products')
               ->withCount([
                   'orders as confirmed_orders_count' => fn($q) => $q->where('status', 'confirme'),
                   'orders as delivered_orders_count'  => fn($q) => $q->where('status', 'livre'),
               ])
               ->get()
               ->map(fn($u) => TeamMember::fromUser($u));

        return [
            'team'     => $team,
            'members'  => $members,
            'products' => Product::all(),
        ];
    }
}
