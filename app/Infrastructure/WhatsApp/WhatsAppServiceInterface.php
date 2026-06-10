<?php 
namespace App\Infrastructure\WhatsApp;

interface WhatsAppServiceInterface
{
    public function send(string $to, string $message): void;
}