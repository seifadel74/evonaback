<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            $table->dropUnique(['cart_id', 'product_id']);
            $table->unique(['cart_id', 'product_id', 'product_variant_id'], 'cart_item_product_variant_unique');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->json('variant_details')->nullable()->after('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
            $table->dropColumn('product_variant_id');
            $table->dropUnique('cart_item_product_variant_unique');
            $table->unique(['cart_id', 'product_id'], 'cart_item_product_unique');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('variant_details');
        });
    }
};
