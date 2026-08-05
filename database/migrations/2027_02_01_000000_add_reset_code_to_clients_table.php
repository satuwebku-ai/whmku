<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Kode reset dikirim lewat email dan disimpan sebagai hash —
            // sama seperti OTP admin, supaya kode mentah tidak pernah
            // tersimpan di database.
            $table->string('reset_code_hash')->nullable()->after('password');
            $table->timestamp('reset_code_expires_at')->nullable()->after('reset_code_hash');
            $table->unsignedTinyInteger('reset_attempts')->default(0)->after('reset_code_expires_at');
            $table->timestamp('email_verified_at')->nullable()->after('reset_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'reset_code_hash', 'reset_code_expires_at',
                'reset_attempts', 'email_verified_at',
            ]);
        });
    }
};
