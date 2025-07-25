<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Tabla de ventas
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('productor_id')->nullable()->constrained('productors')->nullOnDelete();
            $table->date('fecha');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('descuento_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->decimal('paga_con', 12, 2)->default(0);
            $table->decimal('cambio', 12, 2)->default(0);
            $table->boolean('es_credito')->default(false); // True si la venta fue a crédito
            $table->boolean('anulada')->default(false);    // True si se anuló la venta
            $table->timestamps();
        });

        // Detalle de productos vendidos
        Schema::create('venta_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('insumo_id')->constrained('insumos')->cascadeOnDelete();
            $table->decimal('cantidad', 12, 2);
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_detalles');
        Schema::dropIfExists('ventas');
    }
};
