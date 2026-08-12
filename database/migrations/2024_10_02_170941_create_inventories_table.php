<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->string('sku');
            $table->string('estado');
            $table->string('lote');
            $table->date('fecha_vencimiento');
            $table->string('condicion_producto');
            $table->date('fecha_ingreso');
            $table->string('bodega');
            $table->string('comercio');
            $table->string('descripcion_producto');
            $table->integer('cantidad');
            $table->decimal('valor', 10, 2);
            $table->string('tipo');
            $table->text('observaciones');
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('item_id')->constrained('items');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
