<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supply_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('catalog_number')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('supply_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->nullable()->unique();
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('audited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('requested')->index();
            $table->text('request_notes')->nullable();
            $table->text('audit_notes')->nullable();
            $table->timestamp('requested_at')->nullable()->index();
            $table->timestamp('audited_at')->nullable()->index();
            $table->string('received_by_name')->nullable();
            $table->longText('received_by_signature')->nullable();
            $table->string('delivered_by_name')->nullable();
            $table->longText('delivered_by_signature')->nullable();
            $table->timestamps();
        });

        Schema::create('supply_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_request_id')->constrained('supply_requests')->cascadeOnDelete();
            $table->foreignId('supply_product_id')->constrained('supply_products')->restrictOnDelete();
            $table->unsignedInteger('requested_quantity');
            $table->unsignedInteger('received_quantity')->default(0);
            $table->unsignedInteger('missing_quantity')->default(0);
            $table->text('observation')->nullable();
            $table->timestamps();

            $table->unique(['supply_request_id', 'supply_product_id'], 'supply_request_product_unique');
        });

        $now = now();
        $products = [
            1 => 'BOLSA DEPRISA 360 CARTA *100',
            2 => 'BOLSA DEPRISA 360 OFICIO *100',
            3 => 'BOLSA DE SEGURIDAD GLE 25X37 CM *100',
            4 => 'BOLSA DE SEGURIDAD GLE X 52 CM *100',
            5 => 'BOLSA DEPRISA ESTANDAR CARTA *100',
            6 => 'BOLSA DEPRISA ESTANDAR OFICIO *100',
            7 => 'BOLSA TCC TIPO A (GRANDE) *25',
            8 => 'BOLSA TCC TIPO B (MEDIANA) *25',
            9 => 'BOLSA TCC TIPO C (PEQUEÑA) *25',
            10 => 'BOLSA TCC TIPO D (EXTRA PEQ) *25',
            11 => 'BOOMERANG TCC *50',
            12 => 'CAJA DE CAUCHOS *22',
            13 => 'CARPETA PLASTICA',
            14 => 'CINTA PARA IMPRESORA ZEBRA GT 800 GRANDE',
            15 => 'CINTA PARA IMPRESORA ZEBRA GT 800 PEQUEÑA',
            16 => 'TABLA DE APOYO',
            17 => 'CINTA TRANSPARENTE',
            18 => 'COSEDORA',
            19 => 'DARDO TCC *100',
            20 => 'DISPENSADOR DE CINTA',
            21 => 'ESFEROS',
            22 => 'GANCHOS DE COSEDORA CAJA*24 TIRAS',
            23 => 'GANCHOS MARIPOSA METALICO',
            24 => 'HOJAS BOND TROQUELADAS (10.8*11.7)*400 TCC/ALDIA',
            25 => 'HOJAS DE BISTURI',
            26 => 'VINIPEL GRANDE TRASPARENTE',
            27 => 'MARCADOR',
            28 => 'MARCADOR BORRABLE',
            29 => 'METROS',
            30 => 'MULTITOMA',
            31 => 'PACKING DEPRISA *100',
            32 => 'PERFORADORA',
            33 => 'POST-IT',
            34 => 'REGLA',
            35 => 'RESALTADOR',
            36 => 'RESMA CARTA',
            37 => 'ROLLO ROTULO BLANCO 10X12 CM *500',
            38 => 'ROLLO ROTULO BLANCO 10X10 CM *500',
            39 => 'SACAGANCHOS',
            40 => 'TINTA PARA SELLO',
            41 => 'SELLO DEVOLVER FIRMADO Y SELLADO',
            42 => 'SEPARADORES',
            43 => 'STICKER "APRECIADO CLIENTE" (ESPINA DE PESCADO)',
            44 => 'STICKER APRECIADO CLIENTE',
            45 => 'STICKER ATENCION NO EXPONER AL CALOR',
            46 => 'STICKER CONTIENE ALIMENTOS',
            47 => 'STICKER CONTIENE DOCUMENTOS',
            48 => 'STICKER CUIDADO NO ARRUMAR MAS DE 4 CAJAS NO VOLTEAR',
            49 => 'STICKER FAVOR NO VOLTEAR SE DAÑA',
            50 => 'STICKER MERCANCIA FRAGIL NO COLOCAR PESO EXCESIVO',
            51 => 'STICKER MUY FRAGIL NO DOBLAR NI COLOCAR PESO',
            52 => 'STICKER TRATAR CON CUIDADO VIDRIOS PARA AUTOMÓVILES',
            53 => 'STICKER VERIFICADO',
            54 => 'VINIPEL TRASPARENTE PEQUEÑO',
            55 => 'HOJAS TROQUELADAS EXXE',
            56 => 'BOLSAS RADICACIÓN DE DOCUMENTOS *50 OFICIO',
            57 => 'BURBUJA',
            58 => 'ESPUMA DELGADA',
            59 => 'ESPUMA GRUESA',
            60 => 'RESMA CARTA A GRANEL',
            61 => 'VINIPEL GRANDE NEGRO',
            62 => 'BISTURI METALICO',
            63 => 'GANCHOS COSEDORA A GRANEL POR TIRAS',
            64 => 'STICKER DE COLORES CIRCULO 12 COLORES AMARILLO- VERDE- ROJO-VIOLETA',
            65 => 'MULTIPUERTO USB',
            66 => 'ROLLO BLANCO 10X8',
            67 => 'STICKER GLE POR FAVOR ENTREGAR EN HORAS AM',
            68 => 'AZ TAMAÑO CARTA',
            69 => 'STICKER NO COLOCAR PESO ENCIMA 20 ALTO * 15 ANCHO',
            70 => 'ROLLO STICKER SERVIENTREGA *500',
            71 => 'PAPEL RECICLADO',
        ];

        DB::table('supply_products')->insert(
            collect($products)->map(fn ($name, $catalogNumber) => [
                'catalog_number' => $catalogNumber,
                'name' => $name,
                'description' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])->values()->all()
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_request_items');
        Schema::dropIfExists('supply_requests');
        Schema::dropIfExists('supply_products');
    }
};
