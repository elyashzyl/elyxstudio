<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->uuid('checkout_key')->nullable()->unique()->after('confirmation_token');
            $table->string('checkout_session_hash', 64)->nullable()->index()->after('checkout_key');
            $table->timestamp('reservation_expires_at')->nullable()->index()->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['checkout_key', 'checkout_session_hash', 'reservation_expires_at']);
        });
    }
};
