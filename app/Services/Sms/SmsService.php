<?php

namespace App\Services\Sms;

use App\Models\SmsMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Envoi de SMS via une API (Twilio) ou en mode démo (journal), avec historique en base.
 */
class SmsService
{
    /** Convertit un numéro saisi (ex. « 22 123 456 », « 0021622123456 ») au format international E.164 (+21622123456). */
    public function normalize(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        } elseif (strlen($digits) <= 9) {
            $digits = config('sms.default_country_code').ltrim($digits, '0');
        }

        return strlen($digits) >= 8 && strlen($digits) <= 15 ? '+'.$digits : null;
    }

    public function send(?string $phone, string $body, ?int $supplierId = null): SmsMessage
    {
        $to = $this->normalize($phone);
        $driver = (string) config('sms.driver', 'log');
        $message = new SmsMessage(['supplier_id' => $supplierId, 'to' => $to ?? (string) $phone, 'body' => $body, 'driver' => $driver]);

        if (! $to) {
            $message->fill(['status' => 'failed', 'error' => 'Numéro de téléphone invalide.'])->save();

            return $message;
        }

        try {
            match ($driver) {
                'twilio' => $this->sendWithTwilio($message),
                'smsgate' => $this->sendWithSmsGate($message),
                'infobip' => $this->sendWithInfobip($message),
                default => $this->log($message),
            };
        } catch (Throwable $exception) {
            $message->fill(['status' => 'failed', 'error' => mb_substr($exception->getMessage(), 0, 500)]);
        }
        $message->save();

        return $message;
    }

    private function sendWithTwilio(SmsMessage $message): void
    {
        ['sid' => $sid, 'token' => $token, 'from' => $from] = config('sms.twilio');
        if (! $sid || ! $token || ! $from) {
            throw new \RuntimeException('Twilio n’est pas configuré (TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN, TWILIO_FROM).');
        }

        $response = Http::asForm()->withBasicAuth($sid, $token)->timeout(20)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'To' => $message->to,
                'From' => $from,
                'Body' => $message->body,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Twilio : '.($response->json('message') ?? 'erreur HTTP '.$response->status()));
        }
        $message->fill(['status' => 'sent', 'provider_id' => $response->json('sid')]);
    }

    /** API SMS Infobip (POST /sms/2/text/advanced, en-tête « Authorization: App <clé> »). */
    private function sendWithInfobip(SmsMessage $message): void
    {
        ['base_url' => $baseUrl, 'api_key' => $apiKey, 'from' => $from] = config('sms.infobip');
        if (! $baseUrl || ! $apiKey) {
            throw new \RuntimeException('Infobip n’est pas configuré (INFOBIP_BASE_URL, INFOBIP_API_KEY).');
        }
        $baseUrl = str_starts_with($baseUrl, 'http') ? rtrim($baseUrl, '/') : 'https://'.rtrim($baseUrl, '/');

        $response = Http::withHeaders(['Authorization' => 'App '.$apiKey])->acceptJson()->timeout(20)
            ->post($baseUrl.'/sms/2/text/advanced', ['messages' => [[
                'destinations' => [['to' => ltrim($message->to, '+')]],
                'from' => $from,
                'text' => $message->body,
            ]]]);

        $status = $response->json('messages.0.status.groupName');
        if ($response->failed() || in_array($status, ['REJECTED', 'UNDELIVERABLE'], true)) {
            throw new \RuntimeException('Infobip : '.($response->json('requestError.serviceException.text')
                ?? $response->json('messages.0.status.description') ?? 'erreur HTTP '.$response->status()));
        }
        $message->fill(['status' => 'sent', 'provider_id' => $response->json('messages.0.messageId')]);
    }

    /** API cloud de l'application Android « SMS Gateway » : le téléphone envoie le SMS avec sa SIM. */
    private function sendWithSmsGate(SmsMessage $message): void
    {
        ['url' => $url, 'username' => $username, 'password' => $password] = config('sms.smsgate');
        if (! $username || ! $password) {
            throw new \RuntimeException('SMS Gateway n’est pas configuré (SMSGATE_USERNAME, SMSGATE_PASSWORD).');
        }

        $response = Http::withBasicAuth($username, $password)->acceptJson()->timeout(20)
            ->post($url, ['textMessage' => ['text' => $message->body], 'phoneNumbers' => [$message->to]]);

        if ($response->failed()) {
            throw new \RuntimeException('SMS Gateway : '.($response->json('message') ?? 'erreur HTTP '.$response->status()));
        }
        $message->fill(['status' => 'sent', 'provider_id' => $response->json('id')]);
    }

    private function log(SmsMessage $message): void
    {
        Log::info('SMS (mode démo) vers '.$message->to.' : '.$message->body);
        $message->fill(['status' => 'logged']);
    }
}
