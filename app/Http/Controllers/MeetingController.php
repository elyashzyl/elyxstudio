<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MeetingController extends Controller
{
    public function teacherIndex(): Response
    {
        return Inertia::render('Teacher/Meetings', [
            'meetings' => $this->mapMeetings(Meeting::with('user:id,name')->latest()->get()),
        ]);
    }

    public function studentIndex(): Response
    {
        return Inertia::render('Student/Meetings', [
            'meetings' => $this->mapMeetings(Meeting::with('user:id,name')->latest()->get()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()->isTeacher(), 403);
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'room' => ['nullable', 'string', 'max:100'],
            'password' => ['nullable', 'string', 'max:64'],
        ]);

        $room = trim((string) ($data['room'] ?? ''));
        if ($room !== '') {
            $slug = $this->slugRoom($room);
            if ($slug === '') {
                $slug = $this->uniqueRoom();
            } elseif (Meeting::where('room', $slug)->exists()) {
                return back()->withErrors(['room' => 'That room name is already taken. Try another.'])
                    ->withInput();
            }
        } else {
            $slug = $this->uniqueRoom();
        }

        Meeting::create([
            'user_id' => Auth::id(),
            'title' => $data['title'] ?? null,
            'room' => $slug,
            'password' => $data['password'] ?? null,
        ]);

        return redirect()->back();
    }

    public function destroy(Meeting $meeting): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isSuperadmin() || $meeting->user_id === $user->id, 403);

        $meeting->delete();

        return redirect()->back();
    }

    public function verify(Meeting $meeting, Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        if ($meeting->password !== null && hash_equals((string) $meeting->password, (string) $request->input('password'))) {
            return response()->json(['ok' => true]);
        }

        return response()->json(['ok' => false, 'message' => 'Incorrect meeting password.'], 403);
    }

    private function uniqueRoom(): string
    {
        do {
            $room = 'edupulse-'.Str::lower(Str::random(4)).'-'.Str::lower(Str::random(4));
        } while (Meeting::where('room', $room)->exists());

        return $room;
    }

    private function slugRoom(string $room): string
    {
        $slug = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $room), '-'));

        return (string) preg_replace('/-+/', '-', $slug);
    }

    private function mapMeetings($meetings): array
    {
        return $meetings->map(fn (Meeting $m) => [
            'id' => $m->id,
            'title' => $m->title,
            'room' => $m->room,
            'url' => 'https://meet.jit.si/'.$m->room,
            'has_password' => $m->password !== null && $m->password !== '',
            'created_at' => $m->created_at?->toDateTimeString(),
            'owner_id' => $m->user_id,
            'owner_name' => $m->user?->name,
        ])->all();
    }
}