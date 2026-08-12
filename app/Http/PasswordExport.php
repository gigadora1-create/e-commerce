<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use App\Models\Password;

class PasswordExport implements FromCollection
{
    public function collection()
    {
        return Password::all();
    }
}
