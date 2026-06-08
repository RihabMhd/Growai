<?php 
namespace App\Application\WhatsApp\SaveOrderStatusTemplate;

use App\Models\OrderStatus;

class SaveOrderStatusTemplateHandler
{
    public function handle(SaveOrderStatusTemplateCommand $cmd): OrderStatus
    {
        $status = OrderStatus::findOrFail($cmd->statusId);

        if ($cmd->templates !== null)      $status->templates         = $cmd->templates;
        if ($cmd->autoSend !== null)       $status->auto_send         = $cmd->autoSend;
        if ($cmd->whatsappMessage !== null) $status->whatsapp_message = $cmd->whatsappMessage;

        $status->save();
        return $status;
    }
}