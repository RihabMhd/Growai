<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    /**
     * Send a WhatsApp message to a phone number.
     *
     * @param string $to      Phone number in international format (e.g. +213XXXXXXXX)
     * @param string $message  Message body (already rendered with placeholders)
     * @return \Illuminate\Http\Response|\Psr\Http\Message\ResponseInterface
     */
    public function send(string $to, string $message)
    {
        // URL can be stored in config/services.php under 'whatsapp'
        $url = config('services.whatsapp.endpoint');
        $token = config('services.whatsapp.token');

        return Http::withToken($token)
            ->asForm()
            ->post($url, [
                'to' => $to,
                'message' => $message,
            ]);
    }
}
