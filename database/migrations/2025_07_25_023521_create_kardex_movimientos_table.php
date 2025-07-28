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
        Schema::create('kardex_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insumo_id')->constrained()->onDelete('cascade');
            $table->enum('tipo', ['Entrada', 'Salida', 'Ajuste'])->default('Entrada');
            $table->decimal('cantidad', 12, 2);
            $table->decimal('precio_unitario', 12, 2)->nullable();
            $table->decimal('total', 14, 2)->nullable();
            $table->string('referencia')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamp('fecha')->useCurrent();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kardex_movimientos');
    }
};
