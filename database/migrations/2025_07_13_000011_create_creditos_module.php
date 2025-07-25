<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Tabla de créditos
        Schema::create('creditos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('productor_id')->constrained('productors')->cascadeOnDelete();
            $table->date('fecha_entrega');
            $table->enum('moneda', ['C$', 'US$'])->default('C$');
            $table->decimal('total', 12, 2);
            $table->decimal('abonado', 12, 2)->default(0);
            $table->timestamps();
        });

        // Detalle de insumos del crédito
        Schema::create('credito_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credito_id')->constrained('creditos')->cascadeOnDelete();
            $table->foreignId('insumo_id')->constrained('insumos')->cascadeOnDelete();
            $table->decimal('cantidad', 12, 2);
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('interes', 5, 2); // porcentaje mensual
            $table->timestamps();
        });

        // Abonos realizados al crédito
        Schema::create('credito_abonos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credito_id')->constrained('creditos')->cascadeOnDelete();
            $table->decimal('monto', 12, 2);
            $table->date('fecha');
            $table->string('comentario')->nullable();
            $table->enum('tipo', ['efectivo', 'producto'])->default('efectivo');
            $table->string('producto_nombre')->nullable();
            $table->decimal('producto_cantidad', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credito_abonos');
        Schema::dropIfExists('credito_detalles');
        Schema::dropIfExists('creditos');
    }
};
