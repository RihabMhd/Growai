<?php 
namespace App\Application\Teams\UpdateTeamSettings;

use App\Domain\Teams\WhatsAppLanguage;

final class UpdateTeamSettingsCommand
{
    public function __construct(
        public readonly WhatsAppLanguage $language,
    ) {}
}