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
        foreach ([
            'nis' => fn (Blueprint $table) => $table->string('nis', 20)->nullable()->unique(),
            'username' => fn (Blueprint $table) => $table->string('username', 50)->nullable()->unique(),
            'role' => fn (Blueprint $table) => $table->string('role', 20)->default('santri'),
            'tgl_lahir' => fn (Blueprint $table) => $table->date('tgl_lahir')->nullable(),
            'alamat' => fn (Blueprint $table) => $table->string('alamat')->nullable(),
        ] as $column => $definition) {
            if (! Schema::hasColumn('users', $column)) {
                Schema::table('users', $definition);
            }
        }
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
