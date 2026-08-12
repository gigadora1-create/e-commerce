<?php

namespace App\Services;

use Infobip\Api\SmsApi;
use Infobip\Configuration;
use Infobip\Model\SmsAdvancedTextualRequest;
use Infobip\Model\SmsDestination;
use Infobip\Model\SmsTextualMessage;

class InfobipService
{
    protected $api;

    public function __construct()
    {
        $configuration = new Configuration(
            host: config('services.infobip.api_url'),
            apiKey: config('services.infobip.api_key')
        );

        $this->api = new SmsApi(config: $configuration);
    }

    public function sendSms($to, $text)
    {
        $message = new SmsTextualMessage(
            destinations: [new SmsDestination(to: $to)],
            from: config('services.infobip.sender'),
            text: $text
        );

        $request = new SmsAdvancedTextualRequest(messages: [$message]);

        try {
            $smsResponse = $this->api->sendSmsMessage($request);
            return $smsResponse;
        } catch (\Exception $e) {
            throw new \Exception("Failed to send SMS: " . $e->getMessage());
        }
    }
}
