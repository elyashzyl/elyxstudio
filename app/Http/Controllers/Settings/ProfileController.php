<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileCustomizationRequest;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        $prev = $request->session()->get('_previous.url');
        if ($prev && (str_contains($prev, '/teacher/profile') || str_contains($prev, '/student/profile'))) {
            return redirect($prev);
        }

        return to_route('profile.edit');
    }

    /**
     * Update the user's profile customization fields and photo.
     */
    public function customize(ProfileCustomizationRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        DB::transaction(function () use ($request, $user, $validated): void {
            if ($request->hasFile('profile_photo')) {
                $payload = $this->processProfilePhoto($request->file('profile_photo'));

                $user->profilePhoto()->updateOrCreate([], [
                    'mime_type' => $payload['mime_type'],
                    'data' => $payload['data'],
                ]);
                $user->profile_photo_updated_at = now();
            } elseif ($request->boolean('remove_profile_photo')) {
                $user->profilePhoto()->delete();
                $user->profile_photo_updated_at = null;
            }

            if (array_key_exists('bio', $validated)) {
                $user->bio = $validated['bio'];
            }

            $user->save();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile customization updated.')]);

        return back();
    }

    /**
     * Stream a user's profile photo from the database.
     */
    public function avatar(User $user): HttpResponse
    {
        $photo = $user->profilePhoto;
        abort_unless($photo, 404);

        return response($photo->contents(), 200, [
            'Content-Type' => $photo->mime_type,
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Constrain an uploaded profile photo to a maximum dimension while preserving aspect ratio.
     *
     * @return array{mime_type: string, data: string}
     */
    private function processProfilePhoto(UploadedFile $file, int $maxDimension = 512): array
    {
        $mime = $file->getMimeType() ?: 'image/jpeg';
        $contents = $file->get();

        if (! extension_loaded('gd')) {
            return [
                'mime_type' => $mime,
                'data' => base64_encode($contents),
            ];
        }

        $image = @imagecreatefromstring($contents);
        if ($image === false) {
            return [
                'mime_type' => $mime,
                'data' => base64_encode($contents),
            ];
        }

        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $realPath = $file->getRealPath();
            if ($realPath && file_exists($realPath)) {
                $exif = @exif_read_data($realPath);
                $orientation = $exif['Orientation'] ?? null;
                $rotated = match ($orientation) {
                    3 => imagerotate($image, 180, 0),
                    6 => imagerotate($image, -90, 0),
                    8 => imagerotate($image, 90, 0),
                    default => null,
                };
                if ($rotated instanceof \GdImage || is_resource($rotated)) {
                    imagedestroy($image);
                    $image = $rotated;
                }
            }
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= 0 || $height <= 0) {
            imagedestroy($image);

            return [
                'mime_type' => $mime,
                'data' => base64_encode($contents),
            ];
        }

        if ($width > $maxDimension || $height > $maxDimension) {
            $ratio = min($maxDimension / $width, $maxDimension / $height);
            $targetWidth = max(1, (int) round($width * $ratio));
            $targetHeight = max(1, (int) round($height * $ratio));
        } else {
            $targetWidth = $width;
            $targetHeight = $height;
        }

        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        if (in_array($mime, ['image/png', 'image/webp'], true)) {
            imagealphablending($target, false);
            imagesavealpha($target, true);
            $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
            imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);
        }

        imagecopyresampled(
            $target,
            $image,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $width,
            $height
        );

        ob_start();
        $targetMime = $mime;
        if ($mime === 'image/png') {
            imagepng($target, null, 8);
        } elseif ($mime === 'image/webp' && function_exists('imagewebp')) {
            imagewebp($target, null, 85);
        } else {
            $targetMime = 'image/jpeg';
            imagejpeg($target, null, 85);
        }
        $encoded = ob_get_clean();

        imagedestroy($image);
        imagedestroy($target);

        if ($encoded === false || $encoded === '') {
            return [
                'mime_type' => $mime,
                'data' => base64_encode($contents),
            ];
        }

        return [
            'mime_type' => $targetMime,
            'data' => base64_encode($encoded),
        ];
    }
}
