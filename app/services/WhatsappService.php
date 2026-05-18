<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    /**
     * Send a WhatsApp message to a phone number via Twilio.
     *
     * @param string $to      Phone number in international format (e.g. +212679226258)
     * @param string $message  Message body (already rendered with placeholders)
     * @return \Illuminate\Http\Response|\Psr\Http\Message\ResponseInterface
     */
    public function send(string $to, string $message)
    {
        $url = config('services.whatsapp.endpoint');
        $token = config('services.whatsapp.token');
        $from = config('services.whatsapp.from');

        // Twilio uses Basic Auth (AccountSID:AuthToken)
        $credentials = base64_encode($this->extractAccountSid($url) . ':' . $token);

        return Http::withHeaders([
            'Authorization' => 'Basic ' . $credentials,
        ])
            ->asForm()
            ->post($url, [
                'From' => 'whatsapp:' . $from,
                'To' => 'whatsapp:' . $to,
                'Body' => $message,
            ]);
    }

    /**
     * Extract Account SID from Twilio endpoint URL.
     */
    private function extractAccountSid(string $url): string
    {
        // URL format: https://api.twilio.com/2010-04-01/Accounts/ACXXXXX/Messages.json
        preg_match('/Accounts\/([A-Za-z0-9]+)\//', $url, $matches);
        return $matches[1] ?? '';
    }
}
