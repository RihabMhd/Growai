<?php 
namespace App\Infrastructure\WhatsApp;

use Illuminate\Support\Facades\Http;

class TwilioWhatsAppDriver implements WhatsAppServiceInterface
{
    public function send(string $to, string $message): void
    {
        $url   = config('services.whatsapp.endpoint');
        $token = config('services.whatsapp.token');
        $from  = config('services.whatsapp.from');

        $sid         = $this->extractAccountSid($url);
        $credentials = base64_encode("{$sid}:{$token}");

        Http::withHeaders(['Authorization' => 'Basic ' . $credentials])
            ->asForm()
            ->post($url, [
                'From' => 'whatsapp:' . $from,
                'To'   => 'whatsapp:' . $to,
                'Body' => $message,
            ])
            ->throw(); // surface failures
    }

    private function extractAccountSid(string $url): string
    {
        preg_match('/Accounts\/([A-Za-z0-9]+)\//', $url, $m);
        return $m[1] ?? throw new \RuntimeException('Invalid Twilio endpoint URL');
    }
}