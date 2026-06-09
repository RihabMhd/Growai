<?php 

namespace App\Application\Teams\UpdateTeamSettings;

final class UpdateTeamSettingsCommand
{
    public function __construct(public readonly string $whatsappLanguage) {}
}