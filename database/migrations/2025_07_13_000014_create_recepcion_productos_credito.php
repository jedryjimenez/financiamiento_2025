<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recepcion_productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('productor_id')
                ->constrained('productors')
                ->cascadeOnDelete();

            // **Añadido desde add_fk_insumo_to_recepcion_productos**
            $table->foreignId('insumo_id')
                ->constrained('insumos')
                ->cascadeOnDelete();

            $table->string('producto');
            $table->decimal('cantidad_bruta', 12, 2);     // Cantidad entregada
            $table->decimal('humedad', 5, 2);             // %
            $table->decimal('cantidad_neta', 12, 2);      // post-humedad
            $table->decimal('precio_unitario', 12, 2);    // precio por libra
            $table->decimal('total_valor', 12, 2);        // valor bruto total
            $table->decimal('abonado_credito', 12, 2);    // abono a deuda
            $table->decimal('efectivo_pagado', 12, 2)
                ->default(0);                           // excedente efectivo
            $table->text('comentario')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recepcion_productos');
    }
};
