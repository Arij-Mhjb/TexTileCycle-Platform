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

    private function log(SmsMessage $message): void
    {
        Log::info('SMS (mode démo) vers '.$message->to.' : '.$message->body);
        $message->fill(['status' => 'logged']);
    }
}
