<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reward_redemptions')) {
            Schema::table('reward_redemptions', function (Blueprint $table) {
                if (! Schema::hasColumn('reward_redemptions', 'status')) {
                    $table->string('status', 32)->default('used')->after('level');
                }
                if (! Schema::hasColumn('reward_redemptions', 'metadata')) {
                    $table->json('metadata')->nullable()->after('status');
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'equipped_badge')) {
                    $table->string('equipped_badge', 64)->nullable()->after('streak_freeze_active');
                }
                if (! Schema::hasColumn('users', 'active_xp_booster')) {
                    $table->boolean('active_xp_booster')->default(false)->after('equipped_badge');
                }
                if (! Schema::hasColumn('users', 'active_brain_shield')) {
                    $table->boolean('active_brain_shield')->default(false)->after('active_xp_booster');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('reward_redemptions')) {
            Schema::table('reward_redemptions', function (Blueprint $table) {
                if (Schema::hasColumn('reward_redemptions', 'metadata')) {
                    $table->dropColumn('metadata');
                }
                if (Schema::hasColumn('reward_redemptions', 'status')) {
                    $table->dropColumn('status');
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'active_brain_shield')) {
                    $table->dropColumn('active_brain_shield');
                }
                if (Schema::hasColumn('users', 'active_xp_booster')) {
                    $table->dropColumn('active_xp_booster');
                }
                if (Schema::hasColumn('users', 'equipped_badge')) {
                    $table->dropColumn('equipped_badge');
                }
            });
        }
    }
};
