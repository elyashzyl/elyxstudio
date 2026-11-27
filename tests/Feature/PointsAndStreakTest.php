<?php

use App\Helpers\PointsHelper;
use App\Models\StudentPoint;
use App\Models\User;
use App\Services\StreakService;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('activity XP uses the current streak multiplier and syncs the student total', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-06 10:00:00'));

    $student = User::factory()->create([
        'role' => 'student',
        'total_points' => 0,
        'streak_freeze_active' => false,
    ]);

    foreach (['2026-11-04', '2026-11-05'] as $date) {
        $point = new StudentPoint([
            'student_id' => $student->id,
            'activity_type' => 'Quiz',
            'activity_id' => (int) str_replace('-', '', $date),
            'points' => 1,
            'score' => 1,
            'total' => 1,
        ]);
        $point->created_at = Carbon::parse($date . ' 10:00:00');
        $point->updated_at = Carbon::parse($date . ' 10:00:00');
        $point->save();
    }

    expect(StreakService::info($student)['streak'])->toBe(2);
    expect(StreakService::multiplierForReward($student))->toBe(1.2);

    $awarded = PointsHelper::awardFixed($student->id, 'Quiz', 99, 10, 'Quiz reward');

    expect($awarded)->toBe(12);
    expect($student->fresh()->total_points)->toBe(14);
});

test('repeating an already awarded activity does not duplicate XP', function () {
    $student = User::factory()->create([
        'role' => 'student',
        'total_points' => 0,
        'streak_freeze_active' => false,
    ]);

    $first = PointsHelper::awardFixed($student->id, 'Quiz', 10, 5, 'Quiz reward');
    $second = PointsHelper::awardFixed($student->id, 'Quiz', 10, 5, 'Quiz reward');

    expect($first)->toBe(5);
    expect($second)->toBe(5);
    expect(StudentPoint::where('student_id', $student->id)->count())->toBe(1);
    expect($student->fresh()->total_points)->toBe(5);
});
