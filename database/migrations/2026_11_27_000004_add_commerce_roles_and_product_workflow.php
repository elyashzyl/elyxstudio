<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->foreignId('seller_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->string('approval_status')->default('approved')->after('is_active');
            $table->text('rejection_reason')->nullable()->after('approval_status');
            $table->timestamp('submitted_at')->nullable()->after('rejection_reason');
            $table->timestamp('approved_at')->nullable()->after('submitted_at');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();

            $table->index(['approval_status', 'is_active']);
            $table->index(['seller_id', 'approval_status']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['seller_id']);
            $table->dropIndex('products_approval_status_is_active_index');
            $table->dropIndex('products_seller_id_approval_status_index');
            $table->dropColumn([
                'seller_id',
                'approval_status',
                'rejection_reason',
                'submitted_at',
                'approved_at',
                'approved_by',
            ]);
        });
    }
};
