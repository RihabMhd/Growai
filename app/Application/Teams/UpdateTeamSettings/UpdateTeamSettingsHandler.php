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

        if ($cmd->whatsappLanguage !== null) {
            $team->whatsapp_language = WhatsAppLanguage::from($cmd->whatsappLanguage);
        }
        if ($cmd->dispatchAuto !== null) {
            $team->dispatch_auto = $cmd->dispatchAuto;
        }
        if ($cmd->inactiveStrategy !== null) {
            $team->inactive_strategy = $cmd->inactiveStrategy;
        }
        if ($cmd->commissionCurrency !== null) {
            $team->commission_currency = $cmd->commissionCurrency;
        }

        $this->teams->save($team);
    }
}
