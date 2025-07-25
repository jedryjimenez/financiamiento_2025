<?php
// database/migrations/2025_07_14_000007_create_recepcion_insumos_module.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // cabecera
        Schema::create('recepcion_insumos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_factura');
            $table->date('fecha_factura')->default(now()->toDateString());
            $table->enum('tipo_pago', ['contado', 'credito']);
            $table->decimal('total_factura', 12, 2)->default(0);
            $table->decimal('abonado', 12, 2)->default(0);
            $table->foreignId('proveedor_id')
                ->nullable()
                ->constrained('proveedores')
                ->nullOnDelete();
            $table->string('comprobante')->nullable();
            $table->timestamps();
            $table->unique(['proveedor_id', 'numero_factura'], 'recep_prov_fact_unique');
        });

        // ítems
        Schema::create('recepcion_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recepcion_id')
                ->constrained('recepcion_insumos')
                ->cascadeOnDelete();
            $table->foreignId('insumo_id')
                ->constrained('insumos')
                ->cascadeOnDelete();
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_compra', 10, 2);
            $table->decimal('precio_venta', 10, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });

        // abonos
        Schema::create('recepcion_abonos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recepcion_id')
                ->constrained('recepcion_insumos')
                ->cascadeOnDelete();
            $table->date('fecha_abono')->default(now()->toDateString());
            $table->string('comprobante')->nullable();
            $table->decimal('monto', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recepcion_abonos');
        Schema::dropIfExists('recepcion_items');
        Schema::dropIfExists('recepcion_insumos');
    }
};
