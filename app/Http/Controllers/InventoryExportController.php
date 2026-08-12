<?php

namespace App\Http\Controllers;

use App\Exports\InventoryExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class InventoryExportController extends Controller
{
    public function export(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $product = $request->input('product');

        return Excel::download(new InventoryExport($startDate, $endDate, $product), 'inventario.xlsx');
    }
}
