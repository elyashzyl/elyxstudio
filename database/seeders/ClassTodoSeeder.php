<?php

namespace Database\Seeders;

use App\Models\ClassTodo;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClassTodoSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::whereIn('role', ['teacher', 'superadmin'])->first();
        $teacherId = $teacher?->id;

        $todos = [
            [
                'teacher_id' => $teacherId,
                'title' => 'Submit Computer Science Practical Activity',
                'description' => 'Complete the debugging and image enhancement practical exercise before the end of this week.',
                'category' => 'project',
                'due_date' => now()->addDays(3),
                'is_active' => true,
            ],
            [
                'teacher_id' => $teacherId,
                'title' => 'Review Module 3 Notes for Midterm Assessment',
                'description' => 'Read through Module 3 slides and practice with the Speed Quiz in the Game Arcade.',
                'category' => 'reading',
                'due_date' => now()->addDays(5),
                'is_active' => true,
            ],
            [
                'teacher_id' => $teacherId,
                'title' => 'Complete Grade 10 Graduation Portrait & Questionnaire',
                'description' => 'Grade 10 students must upload their yearbook photo and submit their graduation quotes.',
                'category' => 'reminder',
                'due_date' => now()->addDays(7),
                'is_active' => true,
            ],
        ];

        foreach ($todos as $data) {
            ClassTodo::firstOrCreate(
                ['title' => $data['title']],
                $data
            );
        }
    }
}
