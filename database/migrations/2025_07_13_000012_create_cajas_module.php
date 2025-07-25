<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Tabla de cajas
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('apertura_at');
            $table->decimal('monto_inicial', 12, 2);
            $table->dateTime('cierre_at')->nullable();
            $table->decimal('monto_final_sistema', 12, 2)->nullable();
            $table->decimal('monto_final_real', 12, 2)->nullable();
            $table->decimal('diferencia', 12, 2)->nullable();
            $table->text('observacion')->nullable();
            $table->timestamps();
        });

        // Movimientos dentro de la caja
        Schema::create('movimientos_caja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_id')->nullable()->constrained('cajas')->nullOnDelete();
            $table->foreignId('recepcion_id')->nullable()->constrained('recepcion_insumos')->nullOnDelete();
            $table->foreignId('credito_id')->nullable()->constrained('creditos')->nullOnDelete();
            $table->enum('tipo', ['ingreso', 'egreso']);
            $table->decimal('monto', 12, 2);
            $table->date('fecha');
            $table->string('concepto');
            $table->boolean('automatico')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_caja');
        Schema::dropIfExists('cajas');
    }
};
