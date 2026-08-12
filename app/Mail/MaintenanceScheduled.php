<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Program;

class MaintenanceScheduled extends Mailable
{
    use Queueable, SerializesModels;

    public $program;

    public function __construct(Program $program)
    {
        $this->program = $program;
    }

    public function build()
    {
        return $this->subject('Mantenimiento Preventivo Agendado')
                    ->view('emails.maintenance-scheduled');
    }
}