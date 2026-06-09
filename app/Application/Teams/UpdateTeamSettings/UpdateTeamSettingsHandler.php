<?php 

namespace App\Application\Teams\UpdateTeamSettings;

use App\Domain\Teams\TeamRepositoryInterface;
use App\Domain\Teams\Models\WhatsAppLanguage;

class UpdateTeamSettingsHandler
{
    public function __construct(private TeamRepositoryInterface $teams) {}

    public function handle(UpdateTeamSettingsCommand $cmd): void
    {
        $team = $this->teams->getOrCreateDefault();
        $team->whatsapp_language = WhatsAppLanguage::from($cmd->whatsappLanguage);
        $this->teams->save($team);
    }
}