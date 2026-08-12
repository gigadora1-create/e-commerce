<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $names = [
            9 => 'BOLSA TCC TIPO C (PEQUEÑA) *25',
            15 => 'CINTA PARA IMPRESORA ZEBRA GT 800 PEQUEÑA',
            49 => 'STICKER FAVOR NO VOLTEAR SE DAÑA',
            52 => 'STICKER TRATAR CON CUIDADO VIDRIOS PARA AUTOMÓVILES',
            54 => 'VINIPEL TRASPARENTE PEQUEÑO',
            56 => 'BOLSAS RADICACIÓN DE DOCUMENTOS *50 OFICIO',
            68 => 'AZ TAMAÑO CARTA',
        ];

        foreach ($names as $catalogNumber => $name) {
            DB::table('supply_products')
                ->where('catalog_number', $catalogNumber)
                ->update([
                    'name' => $name,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        //
    }
};
