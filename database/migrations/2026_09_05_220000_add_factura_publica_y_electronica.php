<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->boolean('factura_electronica')->default(false)->after('tipo_alta');
            $table->string('alegra_contact_id')->nullable()->after('factura_electronica');
        });

        Schema::table('facturas', function (Blueprint $table) {
            $table->string('token_publico', 64)->nullable()->unique()->after('numero');
            $table->string('alegra_id')->nullable()->after('token_publico');
            $table->timestamp('enviada_whatsapp_at')->nullable()->after('alegra_id');
        });

        DB::table('facturas')->whereNull('token_publico')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                DB::table('facturas')->where('id', $row->id)->update([
                    'token_publico' => Str::random(40),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['factura_electronica', 'alegra_contact_id']);
        });

        Schema::table('facturas', function (Blueprint $table) {
            $table->dropColumn(['token_publico', 'alegra_id', 'enviada_whatsapp_at']);
        });
    }
};
