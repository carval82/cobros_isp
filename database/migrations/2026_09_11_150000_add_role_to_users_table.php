<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('admin')->after('email');
            });
        }

        if (! Schema::hasColumn('users', 'documento')) {
            Schema::table('users', function (Blueprint $table) {
                $after = Schema::hasColumn('users', 'role') ? 'role' : 'email';
                $table->string('documento')->nullable()->after($after);
            });
        }
    }

    public function down(): void
    {
        $cols = [];
        if (Schema::hasColumn('users', 'documento')) {
            $cols[] = 'documento';
        }
        if (Schema::hasColumn('users', 'role')) {
            $cols[] = 'role';
        }
        if ($cols !== []) {
            Schema::table('users', function (Blueprint $table) use ($cols) {
                $table->dropColumn($cols);
            });
        }
    }
};
