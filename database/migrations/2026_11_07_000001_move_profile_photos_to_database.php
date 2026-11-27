<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Profile photos used to live on the local "public" disk, which is wiped whenever the
 * production container is redeployed. They are now stored in the database instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Kept out of the users table so the image is never loaded with every user query.
        Schema::create('user_profile_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('mime_type', 50);
            // Base64 keeps the payload portable across SQLite, MySQL and PostgreSQL drivers.
            $table->longText('data');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('profile_photo_updated_at')->nullable()->after('gender');
        });

        $this->importExistingPhotos();

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('profile_photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_photo_path')->nullable()->after('gender');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('profile_photo_updated_at');
        });

        Schema::dropIfExists('user_profile_photos');
    }

    private function importExistingPhotos(): void
    {
        $disk = Storage::disk('public');
        $now = now();

        DB::table('users')
            ->whereNotNull('profile_photo_path')
            ->select(['id', 'profile_photo_path'])
            ->orderBy('id')
            ->each(function (object $user) use ($disk, $now): void {
                if (! $disk->exists($user->profile_photo_path)) {
                    return;
                }

                DB::table('user_profile_photos')->insert([
                    'user_id' => $user->id,
                    'mime_type' => $disk->mimeType($user->profile_photo_path) ?: 'image/jpeg',
                    'data' => base64_encode($disk->get($user->profile_photo_path)),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('users')->where('id', $user->id)->update(['profile_photo_updated_at' => $now]);
            });
    }
};
