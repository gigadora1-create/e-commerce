<?php

namespace App\Http\Controllers;

use App\Models\SmsLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
 use Carbon\Carbon;

class SendController extends Controller
{
   
    public function index(Request $request)
{
    $search = $request->input('search');
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');
    $rowsPerPage = $request->input('rows', 10); // Valor por defecto de 10 si no se proporciona

    // Consulta de logs con filtros
   $logs = SmsLog::with('user')
        ->when($search, function ($query, $search) {
            return $query->where('phone_number', 'like', "%{$search}%")
                ->orWhere('message', 'like', "%{$search}%");
        })
        ->when($startDate, function ($query, $startDate) {
            return $query->whereDate('sent_at', '>=', $startDate);
        })
        ->when($endDate, function ($query, $endDate) {
            return $query->whereDate('sent_at', '<=', $endDate);
        })
        ->orderBy('id', 'desc')
        ->paginate($rowsPerPage);

    // Total de registros encontrados
    $count = $logs->total();

    // Obtener la cantidad de envíos en el mes actual
    $currentMonth = now()->format('Y-m');
    $monthlyCount = SmsLog::where(DB::raw('DATE_FORMAT(sent_at, "%Y-%m")'), $currentMonth)
        ->count();

    // Obtén la cantidad de envíos de mensajes diarios
    $dailyMessages = SmsLog::select(DB::raw('DATE(sent_at) as date'), DB::raw('count(*) as total'))
        ->groupBy(DB::raw('DATE(sent_at)'))
        ->get()
        ->toArray(); // Convertir a array

    // Obtén la cantidad de envíos de mensajes mensuales por cliente
    $clients = ['Syscom', 'Marykay', 'Derco', 'Inchcape']; // Añade más si lo deseas
    $monthlyClientMessages = [];
    $months = [];

    foreach ($clients as $client) {
        $clientData = SmsLog::where('message', 'like', "%{$client}%")
            ->select(DB::raw('DATE_FORMAT(sent_at, "%Y-%m") as month'), DB::raw('count(*) as total'))
            ->groupBy(DB::raw('DATE_FORMAT(sent_at, "%Y-%m")'))
            ->orderBy(DB::raw('DATE_FORMAT(sent_at, "%Y-%m")'))
            ->get()
            ->toArray(); // Convertir a array

        foreach ($clientData as $data) {
            $months[$data['month']] = \Carbon\Carbon::parse($data['month'])->translatedFormat('F Y');
        }

        $monthlyClientMessages[$client] = collect($clientData)->keyBy('month')->map(function($item) {
            return $item['total'];
        })->toArray(); // Convertir a array
    }

    // Ordena los meses y completa los datos faltantes
    $months = array_keys($months);
    foreach ($monthlyClientMessages as $client => $data) {
        foreach ($months as $month) {
            if (!isset($data[$month])) {
                $monthlyClientMessages[$client][$month] = 0;
            }
        }
    }

    // Mensaje del modal
    $searchMessage = null;
    if ($search || $startDate || $endDate) {
        $searchMessage = "Se encontraron $count envíos de mensajes para la búsqueda realizada.";
    } else {
        $searchMessage = "En el mes de " . \Carbon\Carbon::parse($currentMonth)->translatedFormat('F Y') . ", se encontraron $monthlyCount envíos de mensajes.";
    }

    return view('send.index', compact('logs', 'monthlyCount', 'count', 'currentMonth', 'dailyMessages', 'monthlyClientMessages', 'months', 'searchMessage'));
}




    public function show(SmsLog $smsLog)
    {
        return view('send.show', compact('smsLog'));
    }

    public function create()
    {
        return view('send.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|max:255',
            'message' => 'required|string',
            'status' => 'required|in:Enviado,Fallido',
            'sent_at' => 'required|date',
            'user_id' => 'required|exists:users,id',
        ]);

        SmsLog::create($request->all());

        return redirect()->route('send.index')->with('Enviado', 'Mensajes enviados con éxito.');
    }

    public function edit(SmsLog $smsLog)
    {
        return view('send.edit', compact('smsLog'));
    }

    public function update(Request $request, SmsLog $smsLog)
    {
        $request->validate([
            'phone_number' => 'required|string|max:255',
            'message' => 'required|string',
            'status' => 'required|in:Enviado,Fallido',
            'sent_at' => 'required|date',
            'user_id' => 'required|exists:users,id',
        ]);

        $smsLog->update($request->all());

        return redirect()->route('send.index')->with('success', 'Log de SMS actualizado con éxito.');
    }

    public function destroy(SmsLog $smsLog)
    {
        $smsLog->delete();

        return redirect()->route('send.index')->with('success', 'Log de SMS eliminado con éxito.');
    }
}
