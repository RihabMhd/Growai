<?php

namespace App\Domain\Teams;

use App\Domain\Teams\Models\Team;

interface TeamRepositoryInterface
{
    public function first(): ?Team;
    public function getOrCreateDefault(): Team;
    public function save(Team $team): void;
}