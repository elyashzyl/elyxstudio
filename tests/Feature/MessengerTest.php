<?php

use App\Models\Conversation;
use App\Models\GradeLevel;
use App\Models\Message;
use App\Models\Section;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $gradeLevel = GradeLevel::create(['name' => 'Grade 10']);
    $this->section = Section::create([
        'name' => 'Section A',
        'grade_level_id' => $gradeLevel->id,
    ]);

    $this->teacher = User::factory()->create([
        'role' => 'teacher',
        'messenger_enabled' => true,
        'allow_classmate_messaging' => true,
        'email_verified_at' => now(),
    ]);

    $this->student1 = User::factory()->create([
        'role' => 'student',
        'section_id' => $this->section->id,
        'messenger_enabled' => true,
        'email_verified_at' => now(),
    ]);

    $this->student2 = User::factory()->create([
        'role' => 'student',
        'section_id' => $this->section->id,
        'messenger_enabled' => true,
        'email_verified_at' => now(),
    ]);

    $this->student1->teachers()->attach($this->teacher->id);
    $this->student2->teachers()->attach($this->teacher->id);
});

test('student can message classmate when classmate messaging is enabled', function () {
    $this->actingAs($this->student1);

    $response = $this->post(route('student.messenger.start'), [
        'recipient_id' => $this->student2->id,
        'message' => 'Hello classmate',
    ]);

    $response->assertRedirect();
    expect(Conversation::count())->toBe(1);
    expect(Conversation::first()->messages()->count())->toBe(1);
});

test('student cannot message classmate when teacher disables classmate messaging', function () {
    $this->teacher->update(['allow_classmate_messaging' => false]);
    $this->actingAs($this->student1);

    $response = $this->post(route('student.messenger.start'), [
        'recipient_id' => $this->student2->id,
        'message' => 'Hello classmate',
    ]);

    $response->assertForbidden();
});

test('student cannot message classmate when system disables classmate messaging', function () {
    Setting::set('classmate_messaging_enabled', false);
    $this->actingAs($this->student1);

    $response = $this->post(route('student.messenger.start'), [
        'recipient_id' => $this->student2->id,
        'message' => 'Hello classmate',
    ]);

    $response->assertForbidden();
});

test('student can always message teacher even if classmate messaging is disabled', function () {
    $this->teacher->update(['allow_classmate_messaging' => false]);
    $this->actingAs($this->student1);

    $response = $this->post(route('student.messenger.start'), [
        'recipient_id' => $this->teacher->id,
        'message' => 'Hello teacher',
    ]);

    $response->assertRedirect();
    expect(Conversation::count())->toBe(1);
});

test('user can create a group chat', function () {
    $this->actingAs($this->teacher);

    $response = $this->post(route('teacher.messenger.group.store'), [
        'name' => 'Project Group',
        'participant_ids' => [$this->student1->id, $this->student2->id],
        'message' => 'Welcome to the project group',
    ]);

    $response->assertRedirect();
    $group = Conversation::first();
    expect($group)->not->toBeNull();
    expect($group->is_group)->toBeTrue();
    expect($group->subject)->toBe('Project Group');
    expect($group->participants()->count())->toBe(3);
    expect($group->messages()->count())->toBe(1);
});

test('student cannot add classmates to group chat if classmate messaging is disabled', function () {
    $this->teacher->update(['allow_classmate_messaging' => false]);
    $this->actingAs($this->student1);

    $response = $this->post(route('student.messenger.group.store'), [
        'name' => 'Student Study Group',
        'participant_ids' => [$this->student2->id],
        'message' => 'Secret study session',
    ]);

    $response->assertForbidden();
});

test('student can create group chat with teacher even when classmate messaging is disabled', function () {
    $this->teacher->update(['allow_classmate_messaging' => false]);
    $this->actingAs($this->student1);

    $response = $this->post(route('student.messenger.group.store'), [
        'name' => 'Consultation Group',
        'participant_ids' => [$this->teacher->id],
        'message' => 'Need help with math',
    ]);

    $response->assertRedirect();
    $group = Conversation::first();
    expect($group->is_group)->toBeTrue();
    expect($group->participants()->count())->toBe(2);
});

test('teacher can toggle classmate messaging setting', function () {
    $this->actingAs($this->teacher);

    $response = $this->patch(route('teacher.profile.toggleClassmateMessaging'));
    $response->assertRedirect();
    expect($this->teacher->fresh()->allow_classmate_messaging)->toBeFalse();

    $response = $this->patch(route('teacher.profile.toggleClassmateMessaging'));
    $response->assertRedirect();
    expect($this->teacher->fresh()->allow_classmate_messaging)->toBeTrue();
});

test('superadmin can toggle system-wide classmate messaging setting', function () {
    $superadmin = User::factory()->create([
        'role' => 'superadmin',
        'email_verified_at' => now(),
    ]);
    $this->actingAs($superadmin);

    $response = $this->patch(route('admin.users.toggle-classmate-messaging'));
    $response->assertRedirect();
    expect(Setting::isClassmateMessagingEnabled())->toBeFalse();

    $response = $this->patch(route('admin.users.toggle-classmate-messaging'));
    $response->assertRedirect();
    expect(Setting::isClassmateMessagingEnabled())->toBeTrue();
});

test('messenger lists conversations by their most recent message', function () {
    $olderConversation = Conversation::create([
        'subject' => null,
        'is_group' => false,
        'created_by' => $this->teacher->id,
    ]);
    $olderConversation->participants()->attach([$this->teacher->id, $this->student1->id]);
    $olderMessage = Message::create([
        'conversation_id' => $olderConversation->id,
        'sender_id' => $this->student1->id,
        'body' => 'Older message',
    ]);
    $olderMessage->created_at = now()->subDays(3);
    $olderMessage->updated_at = now()->subDays(3);
    $olderMessage->save();
    $olderConversation->created_at = now()->subDays(3);
    $olderConversation->updated_at = now()->subDays(3);
    $olderConversation->save();

    $recentConversation = Conversation::create([
        'subject' => null,
        'is_group' => false,
        'created_by' => $this->teacher->id,
    ]);
    $recentConversation->participants()->attach([$this->teacher->id, $this->student2->id]);
    $recentMessage = Message::create([
        'conversation_id' => $recentConversation->id,
        'sender_id' => $this->student2->id,
        'body' => 'Recent message',
    ]);
    $recentMessage->created_at = now();
    $recentMessage->updated_at = now();
    $recentMessage->save();
    $recentConversation->created_at = now()->subDays(10);
    $recentConversation->updated_at = now()->subDays(10);
    $recentConversation->save();

    $response = $this->actingAs($this->teacher)->getJson(route('teacher.messenger'));
    $response->assertOk();

    expect($response->json('conversations.0.id'))->toBe($recentConversation->id)
        ->and($response->json('conversations.0.last_message'))->toBe('Recent message');
});

test('conversation is marked unread with unread count for recipient and read for sender', function () {
    $this->actingAs($this->student1);

    $this->post(route('student.messenger.start'), [
        'recipient_id' => $this->student2->id,
        'message' => 'First message',
    ]);

    $conversation = Conversation::first();
    expect($conversation)->not->toBeNull();

    // Check from sender's perspective (student1)
    $responseSender = $this->actingAs($this->student1)
        ->getJson(route('student.messenger'));
    $responseSender->assertOk();
    $convDataSender = collect($responseSender->json('conversations'))->firstWhere('id', $conversation->id);
    expect($convDataSender['unread'])->toBeFalse()
        ->and($convDataSender['unread_count'])->toBe(0)
        ->and($convDataSender['last_mine'])->toBeTrue();

    // Check from recipient's perspective (student2)
    $responseRecipient = $this->actingAs($this->student2)
        ->getJson(route('student.messenger'));
    $responseRecipient->assertOk();
    $convDataRecipient = collect($responseRecipient->json('conversations'))->firstWhere('id', $conversation->id);
    expect($convDataRecipient['unread'])->toBeTrue()
        ->and($convDataRecipient['unread_count'])->toBe(1)
        ->and($convDataRecipient['last_mine'])->toBeFalse();

    // unread-count endpoint
    $unreadCountRes = $this->actingAs($this->student2)->getJson(route('student.messenger.unread'));
    $unreadCountRes->assertOk()->assertJson(['count' => 1]);
});

test('conversation marks as read when viewed and becomes unread again when new message is received', function () {
    $this->actingAs($this->student1);
    $this->post(route('student.messenger.start'), [
        'recipient_id' => $this->student2->id,
        'message' => 'Message 1',
    ]);

    $conversation = Conversation::first();

    // Recipient opens the conversation
    $showResponse = $this->actingAs($this->student2)->get(route('student.messenger.show', $conversation));
    $showResponse->assertOk();

    // After viewing, conversation is now marked read
    $responseRecipient = $this->actingAs($this->student2)->getJson(route('student.messenger'));
    $convDataRecipient = collect($responseRecipient->json('conversations'))->firstWhere('id', $conversation->id);
    expect($convDataRecipient['unread'])->toBeFalse()
        ->and($convDataRecipient['unread_count'])->toBe(0);

    // Unread count is now 0
    $unreadCountRes = $this->actingAs($this->student2)->getJson(route('student.messenger.unread'));
    $unreadCountRes->assertOk()->assertJson(['count' => 0]);

    // Student 1 sends a second message a moment later
    $this->travel(1)->second();

    $this->actingAs($this->student1);
    $this->post(route('student.messenger.send', $conversation), [
        'body' => 'Message 2',
    ]);

    // Recipient should now see it as unread again!
    $responseRecipient2 = $this->actingAs($this->student2)->getJson(route('student.messenger'));
    $convDataRecipient2 = collect($responseRecipient2->json('conversations'))->firstWhere('id', $conversation->id);
    expect($convDataRecipient2['unread'])->toBeTrue()
        ->and($convDataRecipient2['unread_count'])->toBe(1);

    $unreadCountRes2 = $this->actingAs($this->student2)->getJson(route('student.messenger.unread'));
    $unreadCountRes2->assertOk()->assertJson(['count' => 1]);
});

test('messenger shows updated profile name, photo and bio of other users', function () {
    $this->actingAs($this->student1);
    $this->post(route('student.messenger.start'), [
        'recipient_id' => $this->student2->id,
        'message' => 'Hi',
    ]);
    $conversation = Conversation::first();

    $before = $this->actingAs($this->student1)->getJson(route('student.messenger'));
    $conv = collect($before->json('conversations'))->firstWhere('id', $conversation->id);
    expect($conv['other']['avatar'])->toBeNull();
    expect($conv['other']['bio'])->toBeNull();

    $this->student2->forceFill([
        'name' => 'Renamed Classmate',
        'bio' => 'I love math.',
        'profile_photo_updated_at' => now(),
    ])->save();

    $after = $this->actingAs($this->student1)->getJson(route('student.messenger'));
    $conv = collect($after->json('conversations'))->firstWhere('id', $conversation->id);

    expect($conv['other']['name'])->toBe('Renamed Classmate');
    expect($conv['other']['bio'])->toBe('I love math.');
    expect($conv['other']['avatar'])->toContain(route('profile.avatar', $this->student2, false));
    expect(collect($conv['participants'])->firstWhere('id', $this->student2->id)['avatar'])->not->toBeNull();

    $contact = collect($after->json('contacts'))->firstWhere('id', $this->student2->id);
    expect($contact['name'])->toBe('Renamed Classmate');
    expect($contact['avatar'])->not->toBeNull();
    expect($contact['bio'])->toBe('I love math.');

    $show = $this->actingAs($this->student1)->get(route('student.messenger.show', $conversation));
    $show->assertInertia(fn ($page) => $page
        ->where('conversation.other.name', 'Renamed Classmate')
        ->where('conversation.other.bio', 'I love math.')
        ->whereNot('conversation.other.avatar', null)
    );
});

test('viewing conversation provides previous user_last_read_at so frontend can display unread separator', function () {
    $this->actingAs($this->student1);
    $this->post(route('student.messenger.start'), [
        'recipient_id' => $this->student2->id,
        'message' => 'Message 1',
    ]);

    $conversation = Conversation::first();

    // On first open, user_last_read_at should be null
    $firstOpen = $this->actingAs($this->student2)->get(route('student.messenger.show', $conversation));
    $firstOpen->assertOk();
    $convProps = $firstOpen->inertiaProps('conversation');
    expect($convProps['user_last_read_at'])->toBeNull();

    // Now student 2 read it at this point in time
    $this->travel(5)->seconds();
    $this->actingAs($this->student1);
    $this->post(route('student.messenger.send', $conversation), [
        'body' => 'Message 2',
    ]);

    // Student 2 opens it again: user_last_read_at must reflect their PREVIOUS read timestamp
    $secondOpen = $this->actingAs($this->student2)->get(route('student.messenger.show', $conversation));
    $secondOpen->assertOk();
    $convProps2 = $secondOpen->inertiaProps('conversation');
    expect($convProps2['user_last_read_at'])->not->toBeNull();
});

test('user can send message with file attachment and download it', function () {
    \Illuminate\Support\Facades\Storage::fake('local');
    $file = \Illuminate\Http\UploadedFile::fake()->create('project_guidelines.pdf', 500, 'application/pdf');

    $this->actingAs($this->student1);
    $this->post(route('student.messenger.start'), [
        'recipient_id' => $this->teacher->id,
        'message' => 'Here is my document',
    ]);

    $conversation = Conversation::first();

    $response = $this->post(route('student.messenger.send', $conversation), [
        'body' => 'See attached file',
        'attachment' => $file,
    ]);

    $response->assertRedirect();
    $message = $conversation->messages()->latest('id')->first();
    expect($message->attachment_path)->not->toBeNull()
        ->and($message->attachment_name)->toBe('project_guidelines.pdf')
        ->and($message->attachment_type)->toBe('application/pdf');

    // Participant (teacher) can access attachment
    $downloadResponse = $this->actingAs($this->teacher)->get(route('messenger.attachment', $message));
    $downloadResponse->assertOk();

    // Non-participant student cannot access attachment
    $unrelatedStudent = User::factory()->create([
        'role' => 'student',
        'email_verified_at' => now(),
    ]);
    $forbiddenResponse = $this->actingAs($unrelatedStudent)->get(route('messenger.attachment', $message));
    $forbiddenResponse->assertForbidden();
});

test('polling includes attachment attributes', function () {
    \Illuminate\Support\Facades\Storage::fake('local');
    $file = \Illuminate\Http\UploadedFile::fake()->image('diagram.png');

    $this->actingAs($this->teacher);
    $conv = Conversation::create(['subject' => 'Math Help', 'is_group' => false, 'created_by' => $this->teacher->id]);
    $conv->participants()->attach([$this->teacher->id, $this->student1->id]);

    $this->post(route('teacher.messenger.send', $conv), [
        'body' => 'Look at this diagram',
        'attachment' => $file,
    ]);

    $pollResponse = $this->actingAs($this->student1)->getJson(route('student.messenger.poll', $conv));
    $pollResponse->assertOk();
    $messages = $pollResponse->json('messages');
    expect($messages)->toHaveCount(1)
        ->and($messages[0]['attachment_name'])->toBe('diagram.png')
        ->and($messages[0]['attachment_url'])->not->toBeNull();
});

test('user can send voice note audio message with inline streaming disposition', function () {
    \Illuminate\Support\Facades\Storage::fake('local');
    $audioFile = \Illuminate\Http\UploadedFile::fake()->create('voice-note-test.webm', 256, 'audio/webm');

    $this->actingAs($this->teacher);
    $conv = Conversation::create(['subject' => 'Audio Feedback', 'is_group' => false, 'created_by' => $this->teacher->id]);
    $conv->participants()->attach([$this->teacher->id, $this->student1->id]);

    $response = $this->post(route('teacher.messenger.send', $conv), [
        'body' => '',
        'attachment' => $audioFile,
    ]);

    $response->assertRedirect();
    $message = $conv->messages()->latest('id')->first();

    expect($message)->not->toBeNull()
        ->and($message->attachment_path)->not->toBeNull()
        ->and($message->attachment_name)->toBe('voice-note-test.webm')
        ->and($message->attachment_type)->toContain('webm')
        ->and($message->attachment_data)->not->toBeNull();

    // Stream audio attachment: must return inline Content-Disposition so browser player streams it
    $streamResponse = $this->actingAs($this->student1)->get(route('messenger.attachment', $message));
    $streamResponse->assertOk();

    $contentType = (string) $streamResponse->headers->get('Content-Type');
    expect($contentType)->toContain('webm');

    $disposition = (string) $streamResponse->headers->get('Content-Disposition');
    expect($disposition)->toContain('inline');
});

test('sending message dispatches MessageSent broadcast event with conversation and user channels', function () {
    \Illuminate\Support\Facades\Event::fake([
        \App\Events\MessageSent::class,
    ]);

    $this->actingAs($this->teacher);
    $conv = Conversation::create(['subject' => 'Real-Time Chat', 'is_group' => false, 'created_by' => $this->teacher->id]);
    $conv->participants()->attach([$this->teacher->id, $this->student1->id]);

    $this->postJson(route('teacher.messenger.send', $conv), [
        'body' => 'Instant message over WebSockets',
    ])->assertOk();

    \Illuminate\Support\Facades\Event::assertDispatched(\App\Events\MessageSent::class, function ($event) use ($conv) {
        $channels = collect($event->broadcastOn())->map(fn ($c) => (string) $c)->all();

        return $event->conversationId === $conv->id
            && $event->message['body'] === 'Instant message over WebSockets'
            && in_array('presence-chat.conversation.'.$conv->id, $channels, true)
            && in_array('private-chat.user.'.$this->student1->id, $channels, true);
    });
});

test('updating and deleting message dispatches MessageUpdated and MessageDeleted broadcast events', function () {
    \Illuminate\Support\Facades\Event::fake([
        \App\Events\MessageUpdated::class,
        \App\Events\MessageDeleted::class,
    ]);

    $this->actingAs($this->teacher);
    $conv = Conversation::create(['subject' => 'Edit Chat', 'is_group' => false, 'created_by' => $this->teacher->id]);
    $conv->participants()->attach([$this->teacher->id, $this->student1->id]);
    $msg = $conv->messages()->create(['sender_id' => $this->teacher->id, 'body' => 'Initial message']);

    // Update message
    $this->patch(route('teacher.messenger.message.update', [$conv, $msg]), [
        'body' => 'Updated body text',
    ])->assertRedirect();

    \Illuminate\Support\Facades\Event::assertDispatched(\App\Events\MessageUpdated::class, function ($event) use ($conv, $msg) {
        return $event->conversationId === $conv->id
            && $event->message['id'] === $msg->id
            && $event->message['body'] === 'Updated body text';
    });

    // Delete message
    $this->delete(route('teacher.messenger.message.destroy', [$conv, $msg]))->assertRedirect();

    \Illuminate\Support\Facades\Event::assertDispatched(\App\Events\MessageDeleted::class, function ($event) use ($conv, $msg) {
        return $event->conversationId === $conv->id
            && $event->messageId === $msg->id;
    });
});

test('marking conversation as read dispatches ConversationRead broadcast event', function () {
    \Illuminate\Support\Facades\Event::fake([
        \App\Events\ConversationRead::class,
    ]);

    $this->actingAs($this->student1);
    $conv = Conversation::create(['subject' => 'Read receipt', 'is_group' => false, 'created_by' => $this->teacher->id]);
    $conv->participants()->attach([$this->teacher->id, $this->student1->id]);

    $res = $this->postJson(route('student.messenger.read', $conv));
    $res->assertOk()->assertJson(['ok' => true]);

    \Illuminate\Support\Facades\Event::assertDispatched(\App\Events\ConversationRead::class, function ($event) use ($conv) {
        return $event->conversationId === $conv->id
            && $event->userId === $this->student1->id
            && ! empty($event->readAt);
    });
});

test('channels authorization permits conversation participants and denies non-participants', function () {
    if (! class_exists(\Pusher\Pusher::class)) {
        $this->markTestSkipped('Pusher driver is not installed in local vendor.');
    }

    config(['broadcasting.default' => 'reverb']);
    app(\Illuminate\Broadcasting\BroadcastManager::class)->setDefaultDriver('reverb');
    require base_path('routes/channels.php');

    $conv = Conversation::create(['subject' => 'Auth test', 'is_group' => false, 'created_by' => $this->teacher->id]);
    $conv->participants()->attach([$this->teacher->id, $this->student1->id]);

    $unrelatedUser = User::factory()->create(['role' => 'student']);

    // Teacher can join chat.conversation.{id}
    $teacherResponse = $this->actingAs($this->teacher)->postJson('/broadcasting/auth', [
        'channel_name' => 'presence-chat.conversation.'.$conv->id,
        'socket_id' => '12345.67890',
    ]);
    $teacherResponse->assertOk();
    $authData = $teacherResponse->json();
    expect($authData)->toHaveKey('auth');

    // Student 1 can join chat.conversation.{id}
    $studentResponse = $this->actingAs($this->student1)->postJson('/broadcasting/auth', [
        'channel_name' => 'presence-chat.conversation.'.$conv->id,
        'socket_id' => '12345.67890',
    ]);
    $studentResponse->assertOk();

    // Student 1 can join chat.user.{id}
    $userChannelResponse = $this->actingAs($this->student1)->postJson('/broadcasting/auth', [
        'channel_name' => 'private-chat.user.'.$this->student1->id,
        'socket_id' => '12345.67890',
    ]);
    $userChannelResponse->assertOk();

    // Student 1 can join users.{id}
    $usersChannelResponse = $this->actingAs($this->student1)->postJson('/broadcasting/auth', [
        'channel_name' => 'private-users.'.$this->student1->id,
        'socket_id' => '12345.67890',
    ]);
    $usersChannelResponse->assertOk();

    // Student 1 cannot join someone else's user channel
    $forbiddenUserChannel = $this->actingAs($this->student1)->postJson('/broadcasting/auth', [
        'channel_name' => 'private-chat.user.'.$this->teacher->id,
        'socket_id' => '12345.67890',
    ]);
    $forbiddenUserChannel->assertForbidden();

    $forbiddenUsersChannel = $this->actingAs($this->student1)->postJson('/broadcasting/auth', [
        'channel_name' => 'private-users.'.$this->teacher->id,
        'socket_id' => '12345.67890',
    ]);
    $forbiddenUsersChannel->assertForbidden();

    // Unrelated user cannot join conversation channel
    $forbiddenResponse = $this->actingAs($unrelatedUser)->postJson('/broadcasting/auth', [
        'channel_name' => 'presence-chat.conversation.'.$conv->id,
        'socket_id' => '12345.67890',
    ]);
    $forbiddenResponse->assertForbidden();
});
