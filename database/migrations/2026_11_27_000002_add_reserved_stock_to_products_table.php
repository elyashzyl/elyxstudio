<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedInteger('reserved_stock')->default(0)->after('stock');
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->boolean('stock_reserved')->default(false)->after('line_total');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn('stock_reserved');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('reserved_stock');
        });
    }
};
