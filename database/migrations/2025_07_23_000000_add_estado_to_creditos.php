<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('creditos', function (Blueprint $table) {
            $table->enum('estado', ['activo', 'pagado'])
                ->default('activo')
                ->after('abonado');
            $table->timestamp('liquidado_at')->nullable()->after('estado'); // opcional
        });
    }

    public function down(): void
    {
        Schema::table('creditos', function (Blueprint $table) {
            $table->dropColumn(['estado', 'liquidado_at']);
        });
    }
};
