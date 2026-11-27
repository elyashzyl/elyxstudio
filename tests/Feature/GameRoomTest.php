<?php

namespace Tests\Feature;

use App\Events\GameRoomAnswered;
use App\Events\GameRoomUpdated;
use App\Models\Game;
use App\Models\GameCard;
use App\Models\GameRoom;
use App\Models\GradeLevel;
use App\Models\StudentPoint;
use App\Models\TeachingMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class GameRoomTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student;
    private Game $game;
    private GameCard $card1;
    private GameCard $card2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->student = User::factory()->create(['role' => 'student', 'grade' => '8']);

        $this->game = Game::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Biology Speed Battle',
            'subject' => 'Science',
            'grade' => '8',
            'type' => 'speedquiz',
            'xp_reward' => 100,
        ]);

        $this->card1 = GameCard::create([
            'game_id' => $this->game->id,
            'question' => 'What is the powerhouse of the cell?',
            'answer' => 'Mitochondria',
            'options' => ['Mitochondria', 'Nucleus', 'Ribosome', 'Vacuole'],
            'order' => 0,
        ]);

        $this->card2 = GameCard::create([
            'game_id' => $this->game->id,
            'question' => 'What pigment makes plant leaves green?',
            'answer' => 'Chlorophyll',
            'options' => ['Chlorophyll', 'Carotenoid', 'Anthocyanin', 'Melanin'],
            'order' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('app/public/teaching-materials/circulatory.txt'));
        @unlink(storage_path('app/public/teaching-materials/other.txt'));
        @unlink(storage_path('app/public/teaching-materials/cs_sample.txt'));
        @unlink(storage_path('app/public/teaching-materials/photo.txt'));
        @unlink(storage_path('app/public/teaching-materials/resp.txt'));

        parent::tearDown();
    }

    public function test_teacher_can_create_game_room(): void
    {
        $response = $this->actingAs($this->teacher)
            ->post(route('teacher.games.rooms.create', $this->game->id));

        $this->assertDatabaseCount('game_rooms', 1);
        $room = GameRoom::first();

        $this->assertNotNull($room);
        $this->assertEquals(6, strlen($room->pin));
        $this->assertEquals('waiting', $room->status);
        $this->assertEquals($this->game->id, $room->game_id);

        $response->assertRedirect(route('teacher.games.rooms.host', $room->pin));
    }

    public function test_teacher_can_start_game_and_dispatches_event(): void
    {
        Event::fake([GameRoomUpdated::class]);

        $room = GameRoom::create([
            'game_id' => $this->game->id,
            'teacher_id' => $this->teacher->id,
            'pin' => '123456',
            'status' => 'waiting',
            'current_card_index' => 0,
        ]);

        $response = $this->actingAs($this->teacher)
            ->postJson(route('teacher.games.rooms.start', $room->pin));

        $response->assertOk();
        $this->assertEquals('active', $room->fresh()->status);

        Event::assertDispatched(GameRoomUpdated::class, function ($event) use ($room) {
            return $event->pin === $room->pin && $event->payload['status'] === 'active';
        });
    }

    public function test_student_can_join_room_by_pin(): void
    {
        $room = GameRoom::create([
            'game_id' => $this->game->id,
            'teacher_id' => $this->teacher->id,
            'pin' => '654321',
            'status' => 'waiting',
            'current_card_index' => 0,
        ]);

        $response = $this->actingAs($this->student)
            ->post(route('student.games.rooms.join'), ['pin' => '654321']);

        $response->assertRedirect(route('student.games.rooms.play', $room->pin));

        $this->assertDatabaseHas('game_room_participants', [
            'game_room_id' => $room->id,
            'user_id' => $this->student->id,
            'score' => 0,
        ]);
    }

    public function test_student_joining_invalid_pin_returns_error(): void
    {
        $response = $this->actingAs($this->student)
            ->post(route('student.games.rooms.join'), ['pin' => '999999']);

        $response->assertSessionHasErrors(['pin']);
    }

    public function test_live_arena_assigns_each_student_a_different_card_order(): void
    {
        $this->game->update(['is_live' => true]);
        $secondStudent = User::factory()->create(['role' => 'student', 'grade' => '8']);
        $room = GameRoom::create([
            'game_id' => $this->game->id,
            'teacher_id' => $this->teacher->id,
            'pin' => '778800',
            'status' => 'active',
            'current_card_index' => 0,
        ]);

        $firstResponse = $this->actingAs($this->student)
            ->get(route('student.games.rooms.play', $room->pin));
        $secondResponse = $this->actingAs($secondStudent)
            ->get(route('student.games.rooms.play', $room->pin));

        $orders = $room->fresh()->state['player_orders'] ?? [];
        $this->assertCount(2, $orders);
        $this->assertNotSame($orders[(string) $this->student->id], $orders[(string) $secondStudent->id]);

        $firstCardId = $orders[(string) $this->student->id][0];
        $firstCard = $this->game->cards()->find($firstCardId);
        $firstResponse->assertInertia(fn ($page) => $page
            ->where('card.id', $firstCard->id)
            ->where('card.question', $firstCard->question)
        );
        $secondResponse->assertInertia(fn ($page) => $page->has('card.id'));

        $answerResponse = $this->actingAs($this->student)
            ->postJson(route('student.games.rooms.answer', $room->pin), [
                'card_index' => 0,
                'answer' => $firstCard->answer,
            ]);

        $answerResponse->assertOk()->assertJson(['correct' => true]);
    }

    public function test_live_arena_card_order_remains_stable_across_status_polls(): void
    {
        $this->game->update(['is_live' => true]);
        $room = GameRoom::create([
            'game_id' => $this->game->id,
            'teacher_id' => $this->teacher->id,
            'pin' => '778811',
            'status' => 'active',
            'current_card_index' => 0,
        ]);

        $this->actingAs($this->student)->get(route('student.games.rooms.play', $room->pin));

        $initialOrders = $room->fresh()->state['player_orders'][(string) $this->student->id] ?? null;
        $this->assertNotNull($initialOrders);

        // Multiple successive status polls must return the exact same card
        $poll1 = $this->actingAs($this->student)->getJson(route('student.games.rooms.status.student', $room->pin));
        $poll2 = $this->actingAs($this->student)->getJson(route('student.games.rooms.status.student', $room->pin));
        $poll3 = $this->actingAs($this->student)->getJson(route('student.games.rooms.status.student', $room->pin));

        $poll1->assertOk();
        $poll2->assertOk();
        $poll3->assertOk();

        $cardId1 = $poll1->json('card.id');
        $cardId2 = $poll2->json('card.id');
        $cardId3 = $poll3->json('card.id');

        $this->assertSame($cardId1, $cardId2);
        $this->assertSame($cardId2, $cardId3);

        $freshOrders = $room->fresh()->state['player_orders'][(string) $this->student->id] ?? null;
        $this->assertSame($initialOrders, $freshOrders);
    }

    public function test_student_can_answer_correctly_and_accumulate_score(): void
    {
        Event::fake([GameRoomAnswered::class]);

        $room = GameRoom::create([
            'game_id' => $this->game->id,
            'teacher_id' => $this->teacher->id,
            'pin' => '112233',
            'status' => 'active',
            'current_card_index' => 0,
        ]);

        $participant = $room->participants()->create([
            'user_id' => $this->student->id,
            'score' => 0,
            'streak' => 0,
        ]);

        // Submit correct answer (first solver gets 1000 base + 500 first bonus = 1500 pts)
        $response = $this->actingAs($this->student)
            ->postJson(route('student.games.rooms.answer', $room->pin), [
                'card_index' => 0,
                'answer' => 'mitochondria',
            ]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'correct' => true,
                'is_first_solver' => true,
                'first_bonus' => 500,
                'total_score' => 1500,
                'streak' => 1,
            ]);

        $this->assertEquals(1500, $participant->fresh()->score);
        $this->assertEquals(1, $participant->fresh()->streak);
        $this->assertEquals(0, $participant->fresh()->last_answered_card_index);

        Event::assertDispatched(GameRoomAnswered::class, function ($event) use ($room) {
            return $event->pin === $room->pin && $event->payload['correct'] === true && $event->payload['is_first_solver'] === true;
        });
    }

    public function test_first_student_to_answer_receives_fastest_fingers_bonus_and_second_student_does_not(): void
    {
        $secondStudent = User::factory()->create(['role' => 'student', 'grade' => '8']);

        $room = GameRoom::create([
            'game_id' => $this->game->id,
            'teacher_id' => $this->teacher->id,
            'pin' => '334455',
            'status' => 'active',
            'current_card_index' => 0,
        ]);

        $participant1 = $room->participants()->create([
            'user_id' => $this->student->id,
            'score' => 0,
            'streak' => 0,
        ]);

        $participant2 = $room->participants()->create([
            'user_id' => $secondStudent->id,
            'score' => 0,
            'streak' => 0,
        ]);

        // Student 1 answers first -> gets 1000 + 500 bonus = 1500
        $res1 = $this->actingAs($this->student)
            ->postJson(route('student.games.rooms.answer', $room->pin), [
                'card_index' => 0,
                'answer' => 'Mitochondria',
            ]);

        $res1->assertOk()
            ->assertJson([
                'ok' => true,
                'is_first_solver' => true,
                'first_bonus' => 500,
                'points_earned' => 1500,
            ]);
        $this->assertEquals(1500, $participant1->fresh()->score);

        // Student 2 answers second correctly -> gets 1000 (no first bonus)
        $res2 = $this->actingAs($secondStudent)
            ->postJson(route('student.games.rooms.answer', $room->pin), [
                'card_index' => 0,
                'answer' => 'Mitochondria',
            ]);

        $res2->assertOk()
            ->assertJson([
                'ok' => true,
                'is_first_solver' => false,
                'first_bonus' => 0,
                'points_earned' => 1000,
            ]);
        $this->assertEquals(1000, $participant2->fresh()->score);
    }

    public function test_room_status_endpoint_returns_real_time_state_and_first_solver(): void
    {
        $room = GameRoom::create([
            'game_id' => $this->game->id,
            'teacher_id' => $this->teacher->id,
            'pin' => '556677',
            'status' => 'active',
            'current_card_index' => 0,
            'state' => [
                'first_solvers' => [
                    0 => ['user_id' => $this->student->id, 'user_name' => $this->student->name, 'bonus' => 500],
                ],
            ],
        ]);

        $room->participants()->create([
            'user_id' => $this->student->id,
            'score' => 1500,
            'streak' => 1,
            'last_answered_card_index' => 0,
        ]);

        $response = $this->actingAs($this->student)
            ->getJson("/student/games/rooms/{$room->pin}/status");

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'status' => 'active',
                'current_card_index' => 0,
                'first_solver' => [
                    'user_id' => $this->student->id,
                    'user_name' => $this->student->name,
                ],
            ]);
    }

    public function test_teacher_can_view_live_game_create_page(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.games.live.create'));

        $response->assertOk();
    }

    public function test_teacher_can_generate_cards_with_ai(): void
    {
        $response = $this->actingAs($this->teacher)
            ->postJson(route('teacher.games.live.generate-ai'), [
                'prompt' => 'Human circulatory system: heart, blood vessels, red blood cells, arteries, veins',
                'count' => 4,
                'game_type' => 'speedquiz',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'ok',
                'cards' => [
                    '*' => ['question', 'answer', 'options', 'color', 'order'],
                ],
                'count',
            ]);

        $this->assertTrue($response->json('ok'));
        $this->assertNotEmpty($response->json('cards'));
    }

    public function test_teacher_live_create_page_supplies_materials_and_accepts_initial_material_id(): void
    {
        $material = TeachingMaterial::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Cell Biology 101',
            'subject' => 'Biology',
            'description' => 'Mitochondria and Cell organelle details',
            'file_name' => 'cell-biology.pdf',
            'file_path' => 'teaching-materials/cell-biology.pdf',
            'file_size' => 1024,
            'file_type' => 'pdf',
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.games.live.create', ['material_id' => $material->id]));

        $response->assertOk();
        $materials = $response->viewData('page')['props']['materials'];
        $initialId = $response->viewData('page')['props']['initialMaterialId'];

        $this->assertEquals($material->id, $initialId);
        $this->assertNotEmpty($materials);
        $this->assertEquals('Cell Biology 101', $materials[0]['title']);
    }

    public function test_teacher_can_generate_live_arena_cards_based_on_teaching_material(): void
    {
        $content = "The circulatory system pumps blood throughout the human body.\n"
            . "The heart is the muscular organ that powers the entire circulatory system.\n"
            . "Arteries carry oxygenated blood away from the heart to the tissues.\n"
            . "Veins return deoxygenated blood back to the heart.\n"
            . "Capillaries are microscopic vessels where gas exchange occurs.";

        $material = TeachingMaterial::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Circulatory System Guide',
            'subject' => 'Biology',
            'description' => 'Cardiovascular anatomy notes',
            'file_name' => 'circulatory.txt',
            'file_path' => 'teaching-materials/circulatory.txt',
            'file_size' => strlen($content),
            'file_type' => 'txt',
            'file_data' => base64_encode($content),
        ]);

        $response = $this->actingAs($this->teacher)
            ->postJson(route('teacher.games.live.generate-ai'), [
                'material_id' => $material->id,
                'count' => 4,
                'game_type' => 'speedquiz',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'ok',
                'cards' => [
                    '*' => ['question', 'answer', 'options', 'color', 'order'],
                ],
                'count',
                'material' => ['id', 'title', 'subject'],
            ]);

        $this->assertTrue($response->json('ok'));
        $this->assertEquals($material->id, $response->json('material.id'));
        $this->assertEquals('Circulatory System Guide', $response->json('material.title'));
        $this->assertNotEmpty($response->json('cards'));
    }

    public function test_teacher_cannot_generate_cards_from_another_teachers_material_unless_superadmin(): void
    {
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $superadmin = User::factory()->create(['role' => 'superadmin']);

        $content = "Computer networks connect devices together using protocols.";
        $material = TeachingMaterial::create([
            'teacher_id' => $otherTeacher->id,
            'title' => 'Other Teacher Notes',
            'subject' => 'ICT',
            'file_name' => 'other.txt',
            'file_path' => 'teaching-materials/other.txt',
            'file_size' => strlen($content),
            'file_type' => 'txt',
            'file_data' => base64_encode($content),
        ]);

        // Non-owner teacher gets 403
        $this->actingAs($this->teacher)
            ->postJson(route('teacher.games.live.generate-ai'), [
                'material_id' => $material->id,
            ])
            ->assertStatus(403);

        // Superadmin allowed
        $this->actingAs($superadmin)
            ->postJson(route('teacher.games.live.generate-ai'), [
                'material_id' => $material->id,
                'count' => 3,
            ])
            ->assertOk();
    }

    public function test_teacher_can_compile_cards_from_multiple_different_materials(): void
    {
        $content1 = "Photosynthesis occurs in chloroplasts containing chlorophyll pigment.\n"
            . "Sunlight energizes electrons to produce chemical energy ATP.\n"
            . "Carbon dioxide and water are converted into glucose and oxygen.";

        $content2 = "Cellular respiration takes place inside cellular mitochondria.\n"
            . "Glucose molecules are broken down to yield adenosine triphosphate ATP.\n"
            . "Oxygen acts as the final electron acceptor in the respiration pathway.";

        $mat1 = TeachingMaterial::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Photosynthesis Notes',
            'subject' => 'Biology',
            'file_name' => 'photo.txt',
            'file_path' => 'teaching-materials/photo.txt',
            'file_size' => strlen($content1),
            'file_type' => 'txt',
            'file_data' => base64_encode($content1),
        ]);

        $mat2 = TeachingMaterial::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Respiration Notes',
            'subject' => 'Biology',
            'file_name' => 'resp.txt',
            'file_path' => 'teaching-materials/resp.txt',
            'file_size' => strlen($content2),
            'file_type' => 'txt',
            'file_data' => base64_encode($content2),
        ]);

        $response = $this->actingAs($this->teacher)
            ->postJson(route('teacher.games.live.generate-ai'), [
                'material_ids' => [$mat1->id, $mat2->id],
                'count' => 5,
                'game_type' => 'speedquiz',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'ok',
                'cards',
                'count',
                'materials' => [
                    '*' => ['id', 'title', 'subject'],
                ],
            ]);

        $this->assertTrue($response->json('ok'));
        $this->assertCount(2, $response->json('materials'));
        $this->assertNotEmpty($response->json('cards'));
    }

    public function test_teacher_can_create_and_launch_live_game_directly(): void
    {
        $response = $this->actingAs($this->teacher)
            ->post(route('teacher.games.live.store'), [
                'title' => 'Circulatory System Live Clash',
                'subject' => 'Biology',
                'type' => 'speedquiz',
                'xp_reward' => 80,
                'cards' => [
                    [
                        'question' => 'Main organ pumping blood?',
                        'answer' => 'Heart',
                        'options' => ['Heart', 'Lungs', 'Liver', 'Kidneys'],
                        'color' => '#10b981',
                    ],
                    [
                        'question' => 'Vessels carrying blood away from heart?',
                        'answer' => 'Arteries',
                        'options' => ['Arteries', 'Veins', 'Capillaries', 'Platelets'],
                        'color' => '#3b82f6',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('games', [
            'title' => 'Circulatory System Live Clash',
            'subject' => 'Biology',
            'type' => 'speedquiz',
        ]);

        $game = Game::where('title', 'Circulatory System Live Clash')->first();
        $this->assertNotNull($game);
        $this->assertTrue((bool) $game->hidden);
        $this->assertCount(2, $game->cards);

        $room = GameRoom::where('game_id', $game->id)->first();
        $this->assertNotNull($room);
        $this->assertEquals('waiting', $room->status);

        $response->assertRedirect(route('teacher.games.rooms.host', $room->pin));

        // Live game is separated from student arcade games list but visible in active live rooms
        $studentResponse = $this->actingAs($this->student)->get(route('student.games'));
        $studentResponse->assertOk();
        $studentGames = collect($studentResponse->original->getData()['page']['props']['games']);
        $this->assertFalse($studentGames->contains('id', $game->id));

        $activeRooms = collect($studentResponse->original->getData()['page']['props']['activeRooms']);
        $this->assertTrue($activeRooms->contains('id', $room->id));
    }

    public function test_teacher_can_save_live_game_without_opening_room(): void
    {
        $response = $this->actingAs($this->teacher)
            ->post(route('teacher.games.live.store'), [
                'title' => 'Nervous System Arena Match',
                'subject' => 'Biology',
                'type' => 'speedquiz',
                'launch' => false,
                'cards' => [
                    [
                        'question' => 'Basic functional unit of nervous system?',
                        'answer' => 'Neuron',
                        'options' => ['Neuron', 'Nephron', 'Axon', 'Synapse'],
                    ],
                ],
            ]);

        $response->assertRedirect(route('teacher.games.index'));

        $game = Game::where('title', 'Nervous System Arena Match')->first();
        $this->assertNotNull($game);
        $this->assertTrue((bool) $game->is_live);
        $this->assertTrue((bool) $game->hidden);

        // No room was created because the teacher saved it without launching
        $this->assertFalse(GameRoom::where('game_id', $game->id)->exists());

        // Student cannot see it in normal games catalog and no active room exists
        $studentResponse = $this->actingAs($this->student)->get(route('student.games'));
        $studentResponse->assertOk();
        $studentGames = collect($studentResponse->original->getData()['page']['props']['games']);
        $this->assertFalse($studentGames->contains('id', $game->id));

        $activeRooms = collect($studentResponse->original->getData()['page']['props']['activeRooms']);
        $this->assertFalse($activeRooms->contains('game_id', $game->id));
    }

    public function test_student_cannot_play_or_submit_answers_to_live_arena_game_solo(): void
    {
        $liveGame = Game::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Only Live Arena Allowed',
            'subject' => 'Science',
            'grade' => '8',
            'type' => 'speedquiz',
            'is_live' => true,
            'hidden' => true,
        ]);
        $card = GameCard::create([
            'game_id' => $liveGame->id,
            'question' => 'Sample?',
            'answer' => 'Answer',
            'order' => 0,
        ]);

        $progressResponse = $this->actingAs($this->student)
            ->postJson(route('student.games.progress', $liveGame->id), [
                'done_count' => 1,
                'score' => 1,
            ]);
        $progressResponse->assertStatus(403);

        $answerResponse = $this->actingAs($this->student)
            ->postJson(route('student.games.answer', $liveGame->id), [
                'card_id' => $card->id,
            ]);
        $answerResponse->assertStatus(403);
    }

    public function test_teacher_can_preview_and_answer_in_student_live_room(): void
    {
        $room = GameRoom::create([
            'game_id' => $this->game->id,
            'teacher_id' => $this->teacher->id,
            'pin' => '778899',
            'status' => 'active',
            'current_card_index' => 0,
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('student.games.rooms.play', $room->pin));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Student/Games/LiveRoom')
            ->where('room.pin', '778899')
            ->where('room.status', 'active')
        );

        $answerResponse = $this->actingAs($this->teacher)
            ->postJson(route('student.games.rooms.answer', $room->pin), [
                'card_index' => 0,
                'answer' => 'Mitochondria',
            ]);

        $answerResponse->assertOk()
            ->assertJson([
                'ok' => true,
                'correct' => true,
            ]);
    }

    public function test_student_cannot_double_answer_same_card(): void
    {
        $room = GameRoom::create([
            'game_id' => $this->game->id,
            'teacher_id' => $this->teacher->id,
            'pin' => '445566',
            'status' => 'active',
            'current_card_index' => 0,
        ]);

        $room->participants()->create([
            'user_id' => $this->student->id,
            'score' => 1000,
            'streak' => 1,
            'last_answered_card_index' => 0,
        ]);

        $response = $this->actingAs($this->student)
            ->postJson(route('student.games.rooms.answer', $room->pin), [
                'card_index' => 0,
                'answer' => 'mitochondria',
            ]);

        $response->assertStatus(422)
            ->assertJson(['ok' => false]);
    }

    public function test_teacher_ending_game_awards_podium_xp(): void
    {
        Event::fake([GameRoomUpdated::class]);

        $room = GameRoom::create([
            'game_id' => $this->game->id,
            'teacher_id' => $this->teacher->id,
            'pin' => '778899',
            'status' => 'active',
            'current_card_index' => 1,
        ]);

        $room->participants()->create([
            'user_id' => $this->student->id,
            'score' => 2000,
            'streak' => 2,
        ]);

        $response = $this->actingAs($this->teacher)
            ->postJson(route('teacher.games.rooms.end', $room->pin));

        $response->assertOk();
        $this->assertEquals('finished', $room->fresh()->status);

        // 1st place receives configured base XP = 100 points
        $this->assertDatabaseHas('student_points', [
            'student_id' => $this->student->id,
            'points' => 100,
            'score' => 2000,
        ]);

        Event::assertDispatched(GameRoomUpdated::class);
    }

    public function test_active_room_code_is_passed_to_student_and_teacher_game_views(): void
    {
        $activeRoom = GameRoom::create([
            'game_id' => $this->game->id,
            'teacher_id' => $this->teacher->id,
            'pin' => '654321',
            'status' => 'waiting',
            'current_card_index' => 0,
        ]);

        $finishedRoom = GameRoom::create([
            'game_id' => $this->game->id,
            'teacher_id' => $this->teacher->id,
            'pin' => '999888',
            'status' => 'finished',
            'current_card_index' => 1,
        ]);

        // Student games view should receive active room PIN
        $studentResponse = $this->actingAs($this->student)
            ->get(route('student.games'));

        $studentResponse->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Student/Games')
                ->has('activeRooms', 1)
                ->where('activeRooms.0.pin', '654321')
            );

        // Teacher games view should receive active room PIN
        $teacherResponse = $this->actingAs($this->teacher)
            ->get(route('teacher.games.index'));

        $teacherResponse->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Teacher/Games')
                ->has('activeRooms', 1)
                ->where('activeRooms.0.pin', '654321')
            );

        // Student dashboard should receive active room PIN
        $dashboardResponse = $this->actingAs($this->student)
            ->get(route('student.dashboard'));

        $dashboardResponse->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Student/Dashboard')
                ->has('activeLiveRooms', 1)
                ->where('activeLiveRooms.0.pin', '654321')
            );
    }

    public function test_teacher_can_delete_game_room(): void
    {
        Event::fake([GameRoomUpdated::class]);

        $room = GameRoom::create([
            'game_id' => $this->game->id,
            'teacher_id' => $this->teacher->id,
            'pin' => '334455',
            'status' => 'waiting',
            'current_card_index' => 0,
        ]);

        $response = $this->actingAs($this->teacher)
            ->delete(route('teacher.games.rooms.destroy', $room->pin));

        $response->assertRedirect(route('teacher.games.index'));
        $this->assertDatabaseMissing('game_rooms', ['id' => $room->id]);

        Event::assertDispatched(GameRoomUpdated::class, function ($event) use ($room) {
            return $event->pin === $room->pin && ($event->payload['deleted'] ?? false) === true;
        });
    }

    public function test_teacher_cannot_delete_another_teachers_game_room_unless_superadmin(): void
    {
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $superadmin = User::factory()->create(['role' => 'superadmin']);

        $room = GameRoom::create([
            'game_id' => $this->game->id,
            'teacher_id' => $this->teacher->id,
            'pin' => '776655',
            'status' => 'waiting',
            'current_card_index' => 0,
        ]);

        // Other teacher forbidden
        $this->actingAs($otherTeacher)
            ->delete(route('teacher.games.rooms.destroy', $room->pin))
            ->assertStatus(403);

        $this->assertDatabaseHas('game_rooms', ['id' => $room->id]);

        // Superadmin allowed
        $this->actingAs($superadmin)
            ->delete(route('teacher.games.rooms.destroy', $room->pin))
            ->assertRedirect(route('teacher.games.index'));

        $this->assertDatabaseMissing('game_rooms', ['id' => $room->id]);
    }

    public function test_launching_new_room_auto_cleans_older_waiting_or_active_rooms_for_same_game(): void
    {
        $oldRoom = GameRoom::create([
            'game_id' => $this->game->id,
            'teacher_id' => $this->teacher->id,
            'pin' => '111222',
            'status' => 'waiting',
            'current_card_index' => 0,
        ]);

        $this->actingAs($this->teacher)
            ->post(route('teacher.games.rooms.create', $this->game->id));

        $this->assertDatabaseMissing('game_rooms', ['id' => $oldRoom->id]);
        $this->assertDatabaseCount('game_rooms', 1);
        $newRoom = GameRoom::first();
        $this->assertNotEquals('111222', $newRoom->pin);
    }

    public function test_live_room_for_grade_10_is_only_visible_to_grade_10_students_and_hidden_from_other_grades(): void
    {
        $grade10 = GradeLevel::create(['name' => 'Grade 10']);
        $grade9 = GradeLevel::create(['name' => 'Grade 9']);

        $studentGrade10 = User::factory()->create([
            'role' => 'student',
            'grade' => 'Grade 10',
            'grade_level_id' => $grade10->id,
        ]);

        $studentGrade9 = User::factory()->create([
            'role' => 'student',
            'grade' => 'Grade 9',
            'grade_level_id' => $grade9->id,
        ]);

        $gameGrade10 = Game::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Grade 10 Chemistry Showdown',
            'subject' => 'Science',
            'grade' => 'Grade 10',
            'type' => 'speedquiz',
            'xp_reward' => 100,
        ]);

        $room = GameRoom::create([
            'game_id' => $gameGrade10->id,
            'teacher_id' => $this->teacher->id,
            'pin' => '101010',
            'status' => 'waiting',
            'current_card_index' => 0,
        ]);

        // Grade 10 student should see the room on games page
        $this->actingAs($studentGrade10)
            ->get(route('student.games'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Student/Games')
                ->has('activeRooms', 1)
                ->where('activeRooms.0.pin', '101010')
            );

        // Grade 10 student should see the room on dashboard
        $this->actingAs($studentGrade10)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Student/Dashboard')
                ->has('activeLiveRooms', 1)
                ->where('activeLiveRooms.0.pin', '101010')
            );

        // Grade 9 student should NOT see the Grade 10 room on games page
        $this->actingAs($studentGrade9)
            ->get(route('student.games'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Student/Games')
                ->has('activeRooms', 0)
            );

        // Grade 9 student should NOT see the Grade 10 room on dashboard
        $this->actingAs($studentGrade9)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Student/Dashboard')
                ->has('activeLiveRooms', 0)
            );
    }

    public function test_student_in_other_grade_cannot_join_or_play_grade_specific_live_room(): void
    {
        $grade10 = GradeLevel::create(['name' => 'Grade 10']);
        $grade9 = GradeLevel::create(['name' => 'Grade 9']);

        $studentGrade9 = User::factory()->create([
            'role' => 'student',
            'grade' => 'Grade 9',
            'grade_level_id' => $grade9->id,
        ]);

        $studentGrade10 = User::factory()->create([
            'role' => 'student',
            'grade' => 'Grade 10',
            'grade_level_id' => $grade10->id,
        ]);

        $gameGrade10 = Game::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Grade 10 Physics Clash',
            'subject' => 'Science',
            'grade' => 'Grade 10',
            'type' => 'speedquiz',
            'xp_reward' => 100,
        ]);

        $room = GameRoom::create([
            'game_id' => $gameGrade10->id,
            'teacher_id' => $this->teacher->id,
            'pin' => '202020',
            'status' => 'waiting',
            'current_card_index' => 0,
        ]);

        // Grade 9 student attempts to join by PIN
        $joinResponse = $this->actingAs($studentGrade9)
            ->post(route('student.games.rooms.join'), ['pin' => '202020']);

        $joinResponse->assertSessionHasErrors(['pin']);
        $this->assertDatabaseMissing('game_room_participants', [
            'game_room_id' => $room->id,
            'user_id' => $studentGrade9->id,
        ]);

        // Grade 9 student direct access to play page redirects to games with error
        $playResponse = $this->actingAs($studentGrade9)
            ->get(route('student.games.rooms.play', $room->pin));

        $playResponse->assertRedirect(route('student.games'));
        $playResponse->assertSessionHasErrors(['error']);

        // Grade 10 student can join successfully
        $joinSuccess = $this->actingAs($studentGrade10)
            ->post(route('student.games.rooms.join'), ['pin' => '202020']);

        $joinSuccess->assertRedirect(route('student.games.rooms.play', '202020'));
        $this->assertDatabaseHas('game_room_participants', [
            'game_room_id' => $room->id,
            'user_id' => $studentGrade10->id,
        ]);
    }

    public function test_live_room_for_all_grades_is_visible_and_joinable_by_all_grades(): void
    {
        $grade8Student = User::factory()->create(['role' => 'student', 'grade' => 'Grade 8']);
        $grade10Student = User::factory()->create(['role' => 'student', 'grade' => 'Grade 10']);

        $allGradesGame = Game::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Campus Trivia Showdown',
            'subject' => 'General',
            'grade' => 'All Grades',
            'type' => 'speedquiz',
            'xp_reward' => 50,
        ]);

        $room = GameRoom::create([
            'game_id' => $allGradesGame->id,
            'teacher_id' => $this->teacher->id,
            'pin' => '303030',
            'status' => 'waiting',
            'current_card_index' => 0,
        ]);

        // Both grade 8 and grade 10 students see the room
        $this->actingAs($grade8Student)
            ->get(route('student.games'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Student/Games')
                ->has('activeRooms', 1)
                ->where('activeRooms.0.pin', '303030')
            );

        $this->actingAs($grade10Student)
            ->get(route('student.games'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Student/Games')
                ->has('activeRooms', 1)
                ->where('activeRooms.0.pin', '303030')
            );

        // Both can join
        $this->actingAs($grade8Student)
            ->post(route('student.games.rooms.join'), ['pin' => '303030'])
            ->assertRedirect(route('student.games.rooms.play', '303030'));

        $this->actingAs($grade10Student)
            ->post(route('student.games.rooms.join'), ['pin' => '303030'])
            ->assertRedirect(route('student.games.rooms.play', '303030'));
    }

    public function test_card_options_are_deterministically_shuffled_between_host_and_student(): void
    {
        $room = GameRoom::create([
            'game_id' => $this->game->id,
            'teacher_id' => $this->teacher->id,
            'pin' => '554433',
            'status' => 'active',
            'current_card_index' => 0,
        ]);

        $room->participants()->create([
            'user_id' => $this->student->id,
            'score' => 0,
            'streak' => 0,
        ]);

        // Student status fetch
        $studentStatusRes = $this->actingAs($this->student)
            ->getJson("/student/games/rooms/{$room->pin}/status");
        $studentStatusRes->assertOk();
        $studentOptions = $studentStatusRes->json('card.options');

        // Teacher host view fetch
        $teacherStatusRes = $this->actingAs($this->teacher)
            ->getJson(route('teacher.games.rooms.status', $room->pin));
        $teacherStatusRes->assertOk();
        $hostOptions = $teacherStatusRes->json('card.options');

        // Verify options match between student and teacher
        $this->assertNotEmpty($studentOptions);
        $this->assertEquals($studentOptions, $hostOptions);

        // Verify all 4 choices are present
        $this->assertEqualsCanonicalizing(
            ['Mitochondria', 'Nucleus', 'Ribosome', 'Vacuole'],
            $studentOptions
        );
    }

    public function test_teacher_ending_game_awards_podium_xp_with_high_live_scores_without_overflow(): void
    {
        Event::fake([GameRoomUpdated::class]);

        $room = GameRoom::create([
            'game_id' => $this->game->id,
            'teacher_id' => $this->teacher->id,
            'pin' => '385895',
            'status' => 'active',
            'current_card_index' => 1,
        ]);

        // Score 285,600 exceeds smallint unsigned limit (65,535)
        $room->participants()->create([
            'user_id' => $this->student->id,
            'score' => 285600,
            'streak' => 5,
        ]);

        $response = $this->actingAs($this->teacher)
            ->postJson(route('teacher.games.rooms.end', $room->pin));

        $response->assertOk();
        $this->assertEquals('finished', $room->fresh()->status);

        $this->assertDatabaseHas('student_points', [
            'student_id' => $this->student->id,
            'score' => 285600,
            'reason' => 'Live Room PIN ' . $room->pin,
        ]);
    }

    public function test_teacher_can_view_live_rooms_and_top_10_session_leaderboard_in_players_endpoint(): void
    {
        $room = GameRoom::create([
            'game_id' => $this->game->id,
            'teacher_id' => $this->teacher->id,
            'pin' => '445566',
            'status' => 'finished',
            'current_card_index' => 1,
        ]);

        $room->participants()->create([
            'user_id' => $this->student->id,
            'score' => 150000,
            'streak' => 4,
        ]);

        $response = $this->actingAs($this->teacher)
            ->getJson(route('teacher.games.players', $this->game->id));

        $response->assertOk();
        $response->assertJsonStructure([
            'game',
            'players',
            'pending_requests',
            'live_rooms' => [
                '*' => [
                    'id',
                    'pin',
                    'status',
                    'total_participants',
                    'created_at',
                    'leaderboard' => [
                        '*' => ['rank', 'user_id', 'name', 'score', 'streak']
                    ],
                ],
            ],
        ]);

        $this->assertEquals('445566', $response->json('live_rooms.0.pin'));
        $this->assertEquals(150000, $response->json('live_rooms.0.leaderboard.0.score'));
        $this->assertEquals($this->student->id, $response->json('live_rooms.0.leaderboard.0.user_id'));
    }

    public function test_finished_live_rooms_and_standings_are_passed_to_teacher_and_student_games_views(): void
    {
        $finishedRoom = GameRoom::create([
            'game_id' => $this->game->id,
            'teacher_id' => $this->teacher->id,
            'pin' => '778899',
            'status' => 'finished',
            'current_card_index' => 1,
        ]);

        $finishedRoom->participants()->create([
            'user_id' => $this->student->id,
            'score' => 95000,
            'streak' => 3,
        ]);

        // Teacher games view
        $teacherResponse = $this->actingAs($this->teacher)
            ->get(route('teacher.games.index'));

        $teacherResponse->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Teacher/Games')
                ->has('recentFinishedRooms', 1)
                ->where('recentFinishedRooms.0.pin', '778899')
                ->where('recentFinishedRooms.0.winner.score', 95000)
            );

        // Student games view
        $studentResponse = $this->actingAs($this->student)
            ->get(route('student.games'));

        $studentResponse->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Student/Games')
                ->has('recentFinishedRooms', 1)
                ->where('recentFinishedRooms.0.pin', '778899')
                ->where('recentFinishedRooms.0.has_participated', true)
                ->where('recentFinishedRooms.0.my_rank', 1)
                ->where('recentFinishedRooms.0.my_score', 95000)
            );
    }

    public function test_joining_finished_room_pin_redirects_to_podium_play_view(): void
    {
        $finishedRoom = GameRoom::create([
            'game_id' => $this->game->id,
            'teacher_id' => $this->teacher->id,
            'pin' => '334455',
            'status' => 'finished',
            'current_card_index' => 1,
        ]);

        $finishedRoom->participants()->create([
            'user_id' => $this->student->id,
            'score' => 80000,
            'streak' => 2,
        ]);

        $response = $this->actingAs($this->student)
            ->post(route('student.games.rooms.join'), [
                'pin' => '334455',
            ]);

        $response->assertRedirect(route('student.games.rooms.play', '334455'));

        // And visiting the play route loads with leaderboard
        $playResponse = $this->actingAs($this->student)
            ->get(route('student.games.rooms.play', '334455'));

        $playResponse->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Student/Games/LiveRoom')
                ->where('room.status', 'finished')
                ->has('leaderboard', 1)
                ->where('participant.score', 80000)
            );
    }

    public function test_finished_live_arena_results_are_included_in_student_and_teacher_results(): void
    {
        $finishedRoom = GameRoom::create([
            'game_id' => $this->game->id,
            'teacher_id' => $this->teacher->id,
            'pin' => '112233',
            'status' => 'finished',
            'current_card_index' => 1,
        ]);

        $finishedRoom->participants()->create([
            'user_id' => $this->student->id,
            'score' => 120000,
            'streak' => 5,
        ]);

        // Student results
        $studentResults = $this->actingAs($this->student)
            ->get(route('student.results'));

        $studentResults->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Student/Results')
                ->where('activities', fn ($activities) => collect($activities)->contains(
                    fn ($a) => ($a['type'] ?? '') === 'Game' && ($a['is_live'] ?? false) === true && ($a['room_pin'] ?? '') === '112233'
                ))
            );

        // Teacher results
        $teacherResults = $this->actingAs($this->teacher)
            ->get(route('teacher.results'));

        $teacherResults->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Teacher/Results')
                ->where('activities', fn ($activities) => collect($activities)->contains(
                    fn ($a) => ($a['type'] ?? '') === 'Game' && ($a['is_live'] ?? false) === true && ($a['room_pin'] ?? '') === '112233'
                ))
            );
    }
}
