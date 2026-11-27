<?php

namespace App\Http\Controllers;

use App\Enums\ActivityType;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Setting;
use App\Models\User;
use App\Helpers\PointsHelper;
use App\Services\SecureFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class MessengerController extends Controller
{
    private function checkSystemEnabled(): void
    {
        if (! Setting::isMessengerSystemEnabled()) {
            abort(404);
        }
    }

    public function meet(Conversation $conversation): JsonResponse
    {
        $this->checkSystemEnabled();
        $user = Auth::user();
        abort_unless($conversation->participants()->where('user_id', $user->id)->exists(), 403);

        $room = 'edupulse-conv-'.$conversation->id;

        return response()->json([
            'room' => $room,
            'url' => 'https://meet.jit.si/'.$room,
        ]);
    }

    public function status(): JsonResponse
    {
        return response()->json(['enabled' => Setting::isMessengerSystemEnabled()]);
    }

    /**
     * Columns required to expose a user's public profile (name, avatar, bio) to other users.
     * `profile_photo_updated_at` is needed for the `avatar` accessor and its cache-busting version.
     */
    private const PROFILE_COLUMNS = 'id,name,role,profile_photo_updated_at,bio,updated_at';

    private function publicProfile(?User $user): ?array
    {
        return $user?->only(['id', 'name', 'role', 'avatar', 'bio']);
    }

    private static function contactColumns(string $prefix = ''): array
    {
        return array_map(
            fn (string $column) => $prefix.$column,
            explode(',', self::PROFILE_COLUMNS)
        );
    }

    private function conversationQuery(User $user)
    {
        return Conversation::whereHas('participants', fn ($q) => $q->where('user_id', $user->id)
            ->when(! $user->isSuperadmin(), fn ($q) => $q->whereNull('archived_at'))
        )
            ->withMax('messages', 'created_at')
            ->with(['participants:'.self::PROFILE_COLUMNS, 'lastMessage'])
            ->orderByDesc('messages_max_created_at')
            ->orderByDesc('updated_at');
    }

    private function getContacts(User $user)
    {
        if ($user->isSuperadmin()) {
            return User::where('id', '!=', $user->id)->orderBy('name')->get(self::contactColumns());
        }

        if ($user->isTeacher()) {
            return $user->students()->orderBy('name')->get(self::contactColumns('users.'));
        }

        $canMessageClassmates = $user->canMessageClassmates();

        return User::where(function ($q) use ($user, $canMessageClassmates) {
            $q->where(function ($qt) use ($user) {
                $qt->whereIn('id', $user->teachers()->pluck('teacher_id'))
                    ->where('messenger_enabled', true);
            });

            if ($canMessageClassmates) {
                $q->orWhere(function ($q2) use ($user) {
                    $q2->where('role', 'student')
                        ->where('section_id', $user->section_id)
                        ->where('id', '!=', $user->id);
                });
            }
        })->orderBy('name')->get(self::contactColumns());
    }

    private function formatConversation(Conversation $c, User $user): array
    {
        $isGroup = (bool) ($c->is_group || $c->participants->count() > 2);
        $otherParticipant = $c->participants->firstWhere('id', '!=', $user->id);
        $title = $c->subject;
        if (! $title) {
            if ($isGroup) {
                $names = $c->participants->where('id', '!=', $user->id)->pluck('name')->values()->all();
                $title = ! empty($names) ? implode(', ', $names) : 'Group Chat';
            } else {
                $title = $otherParticipant?->name ?? 'Conversation';
            }
        }

        $userPivot = $c->participants->firstWhere('id', $user->id)?->pivot;
        $userLastReadAt = $userPivot?->last_read_at;
        $lastMsg = $c->lastMessage;

        $unreadCount = 0;
        $hasUnread = false;

        if ($lastMsg) {
            if (! $userLastReadAt) {
                $unreadCount = $c->messages()
                    ->where('sender_id', '!=', $user->id)
                    ->count();
                $hasUnread = $unreadCount > 0;
            } elseif ($lastMsg->sender_id !== $user->id && $lastMsg->created_at->gt($userLastReadAt)) {
                $unreadCount = $c->messages()
                    ->where('sender_id', '!=', $user->id)
                    ->where('created_at', '>', $userLastReadAt)
                    ->count();
                $hasUnread = $unreadCount > 0;
            }
        }

        $lastBody = $lastMsg?->body;
        if ((! $lastBody || trim($lastBody) === '') && $lastMsg?->attachment_name) {
            $lastBody = '📎 '.$lastMsg->attachment_name;
        }

        return [
            'id' => $c->id,
            'is_group' => $isGroup,
            'title' => $title,
            'subject' => $c->subject,
            'other' => $this->publicProfile($otherParticipant),
            'participants' => $c->participants->map(fn ($p) => $this->publicProfile($p)),
            'participant_count' => $c->participants->count(),
            'last_message' => $lastBody,
            'last_message_at' => $lastMsg?->created_at,
            'last_mine' => $lastMsg ? $lastMsg->sender_id === $user->id : false,
            'unread' => $hasUnread,
            'unread_count' => $unreadCount,
            'user_last_read_at' => $userLastReadAt,
        ];
    }

    public function index(Request $request): Response|JsonResponse
    {
        $this->checkSystemEnabled();
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $conversations = $this->conversationQuery($user)
            ->get()
            ->map(fn ($c) => $this->formatConversation($c, $user));

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'conversations' => $conversations,
                'contacts' => $this->getContacts($user),
            ]);
        }

        $contacts = $this->getContacts($user);

        return Inertia::render($user->isStudent() ? 'Student/Messenger' : 'Teacher/Messenger', [
            'conversations' => $conversations,
            'contacts' => $contacts,
            'can_message_classmates' => $user->isStudent() ? $user->canMessageClassmates() : true,
            'allow_classmate_messaging' => $user->isTeacher() ? (bool) ($user->allow_classmate_messaging ?? true) : Setting::isClassmateMessagingEnabled(),
            'is_teacher' => $user->isTeacher(),
            'is_superadmin' => $user->isSuperadmin(),
        ]);
    }

    public function show(Conversation $conversation): Response
    {
        $this->checkSystemEnabled();
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($conversation->participants()->where('user_id', $user->id)->exists(), 403);

        $conversation->load(['participants:'.self::PROFILE_COLUMNS, 'messages.sender:id,name']);

        $isGroup = (bool) ($conversation->is_group || $conversation->participants->count() > 2);
        $otherParticipant = $conversation->participants->firstWhere('id', '!=', $user->id);
        $otherLastReadAt = $otherParticipant?->pivot?->last_read_at;

        $userPivot = $conversation->participants->firstWhere('id', $user->id)?->pivot;
        $previousUserLastReadAt = $userPivot?->last_read_at;

        $title = $conversation->subject;
        if (! $title) {
            if ($isGroup) {
                $names = $conversation->participants->where('id', '!=', $user->id)->pluck('name')->values()->all();
                $title = ! empty($names) ? implode(', ', $names) : 'Group Chat';
            } else {
                $title = $otherParticipant?->name ?? 'Conversation';
            }
        }

        $canSend = true;
        $disabledReason = null;
        if ($user->isStudent()) {
            $hasTeacher = $conversation->participants->contains(fn ($p) => in_array($p->role, ['teacher', 'superadmin']));
            if (! $hasTeacher && ! $user->canMessageClassmates()) {
                $canSend = false;
                $disabledReason = 'Classmate messaging is disabled by your teacher. You can only message your teacher.';
            }
        }

        $conversation->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);

        $messageRows = $conversation->messages()->withTrashed()->with('sender:id,name')->orderBy('created_at')->get();

        return Inertia::render($user->isStudent() ? 'Student/Messenger' : 'Teacher/Messenger', [
            'conversation' => [
                'id' => $conversation->id,
                'is_group' => $isGroup,
                'title' => $title,
                'subject' => $conversation->subject,
                'can_send' => $canSend,
                'disabled_reason' => $disabledReason,
                'other' => $this->publicProfile($otherParticipant),
                'participants' => $conversation->participants->map(fn ($p) => $this->publicProfile($p)),
                'participant_count' => $conversation->participants->count(),
                'other_last_read_at' => $otherLastReadAt,
                'user_last_read_at' => $previousUserLastReadAt,
                'messages' => $messageRows->map(fn ($m) => $this->formatMessage($m)),
            ],
            'conversations' => $this->conversationQuery($user)
                ->get()
                ->map(fn ($c) => $this->formatConversation($c, $user)),
            'contacts' => $this->getContacts($user),
            'can_message_classmates' => $user->isStudent() ? $user->canMessageClassmates() : true,
            'allow_classmate_messaging' => $user->isTeacher() ? (bool) ($user->allow_classmate_messaging ?? true) : Setting::isClassmateMessagingEnabled(),
            'is_teacher' => $user->isTeacher(),
            'is_superadmin' => $user->isSuperadmin(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->checkSystemEnabled();
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'recipient_id' => ['required', 'integer', 'exists:users,id'],
            'message' => ['required', 'string', 'max:10000'],
        ]);

        $recipient = User::findOrFail($validated['recipient_id']);

        if ($user->isStudent() && $recipient->isStudent() && ! $user->canMessageClassmates()) {
            abort(403, 'Classmate messaging is disabled. You can only message your teacher.');
        }

        abort_if($recipient->role === 'teacher' && ! $recipient->messenger_enabled, 403, 'This teacher has disabled messenger.');

        $existing = Conversation::where('is_group', false)
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $recipient->id))
            ->first();

        $conversation = null;

        if ($existing) {
            $msg = $existing->messages()->create(['sender_id' => $user->id, 'body' => $validated['message']]);
            $existing->touch();
            $conversation = $existing;
            $this->broadcastMessageSent($conversation, $msg, $user);
        } else {
            $conversation = Conversation::create(['subject' => null, 'is_group' => false, 'created_by' => $user->id]);
            $conversation->participants()->attach([$user->id, $recipient->id]);
            $msg = $conversation->messages()->create(['sender_id' => $user->id, 'body' => $validated['message']]);
            $this->broadcastMessageSent($conversation, $msg, $user);
        }

        return redirect()->route($user->isStudent() ? 'student.messenger.show' : 'teacher.messenger.show', $conversation);
    }

    public function storeGroup(Request $request): RedirectResponse
    {
        $this->checkSystemEnabled();
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'participant_ids' => ['required', 'array', 'min:1'],
            'participant_ids.*' => ['required', 'integer', 'exists:users,id'],
            'message' => ['nullable', 'string', 'max:10000'],
        ]);

        if ($user->isStudent() && ! $user->canMessageClassmates()) {
            $hasStudent = User::whereIn('id', $validated['participant_ids'])
                ->where('role', 'student')
                ->exists();
            if ($hasStudent) {
                abort(403, 'Classmate messaging is disabled. You can only message teachers.');
            }
        }

        $conversation = Conversation::create([
            'subject' => $validated['name'],
            'is_group' => true,
            'created_by' => $user->id,
        ]);

        $participants = array_values(array_unique(array_merge([$user->id], $validated['participant_ids'])));
        $conversation->participants()->attach($participants);

        if (! empty($validated['message'])) {
            $msg = $conversation->messages()->create([
                'sender_id' => $user->id,
                'body' => $validated['message'],
            ]);
            $conversation->touch();
            $this->broadcastMessageSent($conversation, $msg, $user);
        }

        return redirect()->route($user->isStudent() ? 'student.messenger.show' : 'teacher.messenger.show', $conversation);
    }

    public function sendMessage(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $this->checkSystemEnabled();
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($conversation->participants()->where('user_id', $user->id)->exists(), 403);

        if ($user->isStudent() && ! $user->canMessageClassmates()) {
            $hasTeacher = $conversation->participants()->whereIn('role', ['teacher', 'superadmin'])->exists();
            if (! $hasTeacher) {
                abort(403, 'Classmate messaging is disabled by your teacher. You can only message your teacher.');
            }
        }

        $isGroup = (bool) ($conversation->is_group || $conversation->participants()->count() > 2);
        if (! $isGroup) {
            $otherParticipant = $conversation->participants()->where('user_id', '!=', $user->id)->first();
            if ($otherParticipant && $otherParticipant->role !== 'student' && ! $otherParticipant->messenger_enabled) {
                abort(403, 'This user has disabled messenger.');
            }
        }

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:10000', 'required_without:attachment'],
            'attachment' => ['nullable', 'file', 'max:10240', 'required_without:body'],
        ]);

        $attachmentPath = null;
        $attachmentName = null;
        $attachmentType = null;
        $attachmentSize = null;
        $attachmentData = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();
            $attachmentType = $file->getClientMimeType() ?: $file->getMimeType();
            $attachmentSize = $file->getSize();

            $attachmentPath = SecureFileService::store($file, 'messenger-attachments');

            if ($attachmentSize <= 5 * 1024 * 1024) {
                $realPath = $file->getRealPath();
                if ($realPath && file_exists($realPath)) {
                    $content = @file_get_contents($realPath);
                    if ($content !== false) {
                        $attachmentData = base64_encode($content);
                    }
                }
            }
        }

        $message = $conversation->messages()->create([
            'sender_id' => $user->id,
            'body' => $validated['body'] ?? '',
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'attachment_type' => $attachmentType,
            'attachment_size' => $attachmentSize,
            'attachment_data' => $attachmentData,
        ]);
        $conversation->touch();
        $conversation->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);

        $this->broadcastMessageSent($conversation, $message, $user);

        // Award XP every 1000 unique substantial messages (10+ chars, no duplicate spam)
        $messageMinChars = (int) config('gamification.message_min_chars');
        $messageMilestone = (int) config('gamification.message_milestone');
        $messageMilestoneXp = (int) config('gamification.message_milestone_xp');
        $messageCount = Message::where('sender_id', $user->id)
            ->whereRaw('LENGTH(body) >= '.$messageMinChars)
            ->distinct('body')
            ->count('body');
        if ($messageCount > 0 && $messageCount % $messageMilestone === 0) {
            PointsHelper::awardFixed(
                $user->id,
                ActivityType::Message->value,
                $messageCount,
                $messageMilestoneXp,
                "{$messageCount} messages milestone",
            );
        }

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $this->formatMessage($message),
            ]);
        }

        return redirect()->route($user->isStudent() ? 'student.messenger.show' : 'teacher.messenger.show', $conversation);
    }

    public function updateMessage(Request $request, Conversation $conversation, Message $message): RedirectResponse
    {
        $this->checkSystemEnabled();
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($conversation->participants()->where('user_id', $user->id)->exists(), 403);
        abort_if($message->sender_id !== $user->id, 403, 'You can only edit your own messages.');
        abort_if($message->conversation_id !== $conversation->id || $message->trashed(), 404);

        $validated = $request->validate(['body' => ['required', 'string', 'max:10000']]);

        $message->update([
            'body' => $validated['body'],
            'edited_at' => now(),
        ]);

        $this->broadcastMessageUpdated($conversation, $message);

        return redirect()->route($user->isStudent() ? 'student.messenger.show' : 'teacher.messenger.show', $conversation);
    }

    public function destroyMessage(Conversation $conversation, Message $message): RedirectResponse
    {
        $this->checkSystemEnabled();
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($conversation->participants()->where('user_id', $user->id)->exists(), 403);
        abort_if($message->sender_id !== $user->id, 403, 'You can only remove your own messages.');
        abort_if($message->conversation_id !== $conversation->id || $message->trashed(), 404);

        $messageId = $message->id;
        $message->delete();

        $this->broadcastMessageDeleted($conversation, $messageId);

        return redirect()->route($user->isStudent() ? 'student.messenger.show' : 'teacher.messenger.show', $conversation);
    }

    public function archive(Conversation $conversation): RedirectResponse
    {
        $this->checkSystemEnabled();
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($conversation->participants()->where('user_id', $user->id)->exists(), 403);

        if ($user->isSuperadmin()) {
            $conversation->delete();
        } else {
            $conversation->participants()->updateExistingPivot($user->id, ['archived_at' => now()]);
        }

        return redirect()->route($user->isStudent() ? 'student.messenger' : 'teacher.messenger');
    }

    public function poll(Request $request, Conversation $conversation)
    {
        $this->checkSystemEnabled();
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($conversation->participants()->where('user_id', $user->id)->exists(), 403);

        $since = $request->get('since');
        $sinceDate = null;
        if ($since) {
            try {
                $sinceDate = \Carbon\Carbon::parse($since);
            } catch (\Throwable) {
                $sinceDate = null;
            }
        }

        $otherLastReadAt = $conversation->participants()
            ->where('user_id', '!=', $user->id)
            ->first()?->pivot?->last_read_at;

        $messages = $conversation->messages()
            ->withTrashed()
            ->with('sender:id,name')
            ->when($sinceDate, fn ($q) => $q->where('created_at', '>', $sinceDate))
            ->orderBy('created_at')
            ->get()
            ->map(fn ($m) => $this->formatMessage($m));

        $conversation->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);

        return response()->json(['messages' => $messages, 'other_last_read_at' => $otherLastReadAt]);
    }

    public function markAsRead(Conversation $conversation): JsonResponse
    {
        $this->checkSystemEnabled();
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($conversation->participants()->where('user_id', $user->id)->exists(), 403);

        $now = now();
        $conversation->participants()->updateExistingPivot($user->id, ['last_read_at' => $now]);

        try {
            broadcast(new \App\Events\ConversationRead(
                $conversation->id,
                $user->id,
                $now->toIso8601String()
            ))->toOthers();
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json(['ok' => true, 'read_at' => $now->toIso8601String()]);
    }

    public function unreadCount(): JsonResponse
    {
        $this->checkSystemEnabled();
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $count = Conversation::whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->with(['participants:id,name,role', 'lastMessage'])
            ->get()
            ->filter(function ($c) use ($user) {
                $pivot = $c->participants->firstWhere('id', $user->id)?->pivot;
                $lastMsg = $c->lastMessage;
                if (! $lastMsg || $lastMsg->sender_id === $user->id) {
                    return false;
                }
                if (! $pivot?->last_read_at) {
                    return true;
                }

                return $lastMsg->created_at->gt($pivot->last_read_at);
            })
            ->count();

        return response()->json(['count' => $count]);
    }

    public function attachment(Message $message): SymfonyResponse
    {
        $this->checkSystemEnabled();
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $conversation = $message->conversation;
        abort_unless($conversation, 404);
        abort_unless(
            $user->isSuperadmin() || $conversation->participants()->where('user_id', $user->id)->exists(),
            403
        );

        $resolvedPath = $message->getResolvedAttachmentPath();
        if (! $resolvedPath || ! file_exists($resolvedPath)) {
            abort(404, 'Attachment not found.');
        }

        $mime = $message->attachment_type ?: mime_content_type($resolvedPath) ?: 'application/octet-stream';
        $name = $message->attachment_name ?: basename($resolvedPath);

        $isInline = str_starts_with($mime, 'image/')
            || $mime === 'application/pdf'
            || str_starts_with($mime, 'audio/')
            || str_starts_with($mime, 'video/');
        $disposition = HeaderUtils::makeDisposition(
            $isInline ? HeaderUtils::DISPOSITION_INLINE : HeaderUtils::DISPOSITION_ATTACHMENT,
            $name
        );

        return response()->file($resolvedPath, [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposition,
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    protected function formatMessage(Message $m): array
    {
        return [
            'id' => $m->id,
            'sender_id' => $m->sender_id,
            'sender_name' => $m->sender?->name ?? 'Unknown',
            'body' => $m->body,
            'attachment_url' => $m->attachment_path ? route('messenger.attachment', $m) : null,
            'attachment_name' => $m->attachment_name,
            'attachment_type' => $m->attachment_type,
            'attachment_size' => $m->attachment_size,
            'created_at' => $m->created_at,
            'edited_at' => $m->edited_at,
            'deleted_at' => $m->deleted_at,
        ];
    }

    private function broadcastMessageSent(Conversation $conversation, Message $message, User $sender): void
    {
        try {
            $conversation->loadMissing('participants:'.self::PROFILE_COLUMNS, 'lastMessage');
            $recipientIds = $conversation->participants
                ->where('id', '!=', $sender->id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $messageData = $this->formatMessage($message);
            $summary = $this->formatConversation($conversation, $sender);

            broadcast(new \App\Events\MessageSent(
                $conversation->id,
                $messageData,
                $summary,
                $recipientIds
            ))->toOthers();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function broadcastMessageUpdated(Conversation $conversation, Message $message): void
    {
        try {
            $conversation->loadMissing('participants:id,name,role');
            $message->loadMissing('sender:id,name');

            broadcast(new \App\Events\MessageUpdated(
                $conversation->id,
                $this->formatMessage($message)
            ))->toOthers();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function broadcastMessageDeleted(Conversation $conversation, int $messageId): void
    {
        try {
            broadcast(new \App\Events\MessageDeleted(
                $conversation->id,
                $messageId
            ))->toOthers();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
