<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StateChanged
{
    use Dispatchable, SerializesModels;

    public $phoneNumber;
    public $message;

    public function __construct($phoneNumber, $message)
    {
        $this->phoneNumber = $phoneNumber;
        $this->message = $message;
    }
}
