<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::whereIn('role', ['teacher', 'superadmin'])->first();
        $authorId = $teacher?->id;

        $announcements = [
            [
                'user_id' => $authorId,
                'title' => 'Welcome to 2nd Quarter & Academic Roadmap',
                'content' => "Welcome to the second quarter learners! Over the coming weeks, please check your learning modules, complete weekly seatworks, and stay up to date with quiz schedules. Remember to review your teacher feedback in Results to maximize your performance and earn bonus XP on the leaderboard.",
                'category' => 'general',
                'is_pinned' => true,
                'target_role' => 'student',
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
            [
                'user_id' => $authorId,
                'title' => 'Upcoming Midterm Assessment & Practical Review',
                'content' => "Midterm examinations and practical submissions are approaching. Please ensure all outstanding quizzes and practical activities are submitted prior to closing times. Teachers will be holding review sessions this week.",
                'category' => 'academic',
                'is_pinned' => false,
                'target_role' => 'student',
                'created_at' => now()->subHours(18),
                'updated_at' => now()->subHours(18),
            ],
            [
                'user_id' => $authorId,
                'title' => 'Laboratory & Practical Submission Reminder',
                'content' => "Make sure to test your practical exercises and upload required submission files or external links before the deadline. Late attempts will be closed automatically once the timer expires.",
                'category' => 'reminder',
                'is_pinned' => false,
                'target_role' => 'student',
                'created_at' => now()->subHours(5),
                'updated_at' => now()->subHours(5),
            ],
        ];

        foreach ($announcements as $data) {
            Announcement::firstOrCreate(
                ['title' => $data['title']],
                $data
            );
        }
    }
}
