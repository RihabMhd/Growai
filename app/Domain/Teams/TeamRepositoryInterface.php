<?php 
namespace App\Domain\Teams;

interface TeamRepositoryInterface
{
    public function first(): ?object;
    public function firstOrCreate(): object;
    public function save(object $team): void;
}