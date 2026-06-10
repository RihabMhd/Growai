<?php 
namespace App\Application\Teams\UpdateTeamSettings;

final class UpdateTeamSettingsCommand
{
    public function __construct(
        public readonly ?string $whatsappLanguage = null,
        public readonly ?bool   $dispatchAuto     = null,
        public readonly ?string $inactiveStrategy = null,
        public readonly ?string $commissionCurrency = null,
    ) {}
}