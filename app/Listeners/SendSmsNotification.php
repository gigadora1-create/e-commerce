<?php

namespace App\Listeners;

use App\Events\StateChanged;
use App\Services\InfobipService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendSmsNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected $infobipService;

    public function __construct(InfobipService $infobipService)
    {
        $this->infobipService = $infobipService;
    }

    public function handle(StateChanged $event)
    {
        $this->infobipService->sendSms($event->phoneNumber, $event->message);
    }
}
