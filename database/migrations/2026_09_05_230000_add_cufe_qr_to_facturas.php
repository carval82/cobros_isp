<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->string('cufe', 120)->nullable()->after('alegra_id');
            $table->text('qr_code')->nullable()->after('cufe');
            $table->string('estado_dian', 40)->nullable()->after('qr_code');
            $table->string('alegra_numero', 40)->nullable()->after('estado_dian');
            $table->string('alegra_pdf_url', 500)->nullable()->after('alegra_numero');
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropColumn(['cufe', 'qr_code', 'estado_dian', 'alegra_numero', 'alegra_pdf_url']);
        });
    }
};
