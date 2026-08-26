<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 1. product_categories.type -- menentukan jenis produk di dalamnya
     *    (hosting biasa vs VPS/cloud). Dipakai form Tambah Produk untuk
     *    menyesuaikan isian & menyaring pilihan server, supaya admin
     *    tidak bisa salah memasangkan produk hosting ke server cloud
     *    atau sebaliknya.
     *
     * 2. products.billing_mode -- khusus produk VPS: mau ditagih dari
     *    saldo per jam, atau invoice berkala seperti hosting biasa.
     */
    public function up(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->enum('type', ['hosting', 'vps'])->default('hosting')->after('slug');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->enum('billing_mode', ['invoice', 'deposit'])->default('invoice')->after('billing_cycle');
        });
    }

    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('billing_mode');
        });
    }
};
