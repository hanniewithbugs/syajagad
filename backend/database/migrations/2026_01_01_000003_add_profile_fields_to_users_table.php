<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nis', 20)->nullable()->unique()->after('id');
            $table->string('username', 50)->nullable()->unique()->after('email');
            $table->string('role', 20)->default('santri')->after('password');
            $table->date('tgl_lahir')->nullable()->after('role');
            $table->string('alamat')->nullable()->after('tgl_lahir');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['nis']);
            $table->dropUnique(['username']);
            $table->dropColumn(['nis', 'username', 'role', 'tgl_lahir', 'alamat']);
        });
    }
};
