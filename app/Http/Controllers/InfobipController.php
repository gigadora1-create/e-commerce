<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\InfobipService;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MessagesImport;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Auth;

class InfobipController extends Controller
{
    protected $infobipService;

    public function __construct(InfobipService $infobipService)
    {
        $this->infobipService = $infobipService;
    }

    public function sendSMS(Request $request)
    {
        $messages = [];
        $messagesCount = 0;

        if ($request->hasFile('excel_file')) {
            $data = Excel::toArray(new MessagesImport, $request->file('excel_file'))[0];
            $messages = array_filter($data, function ($message) {
                // Verifica que ambos campos estén presentes y no sean solo espacios en blanco
                if (isset($message[0]) && isset($message[1]) && trim($message[0]) !== '' && trim($message[1]) !== '') {
                    // Verifica que el número de teléfono tenga exactamente 12 dígitos
                    return strlen($message[0]) === 12 && is_numeric($message[0]);
                }
                return false;
            });

            $messagesCount = count($messages);

            // Verifica si el archivo tiene exactamente dos columnas
            if ($messagesCount === 0 || !isset($data[0][0]) || !isset($data[0][1])) {
                return redirect()->back()->with('error', 'El archivo debe contener dos columnas: "Número de Teléfono" y "Mensaje", y los números de teléfono deben tener exactamente 12 dígitos con el prefijo 57.');
            }
        } else {
            return redirect()->back()->with('error', 'Por favor, cargue un archivo válido.');
        }

        // Obtener los registros de la tabla sms_logs junto con la información del usuario
        $smsLogs = SmsLog::with('user')->orderBy('sent_at', 'desc')->get();

        return view('send-sms', compact('messages', 'messagesCount', 'smsLogs'));
    }


    public function sendBulkSMS(Request $request)
    {
        $messages = $request->input('messages', []);
        $user = Auth::user();

        try {
            foreach ($messages as $message) {
                $this->infobipService->sendSms($message['phone_number'], $message['message']);

                // Guardar el registro en la tabla sms_logs
                SmsLog::create([
                    'phone_number' => $message['phone_number'],
                    'message' => $message['message'],
                    'status' => 'Enviado',
                    'sent_at' => now(),
                    'user_id' => $user->id
                ]);
            }

            return response()->json(['success' => 'Mensajes enviados correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al enviar mensajes: ' . $e->getMessage()], 500);
        }
    }
}
