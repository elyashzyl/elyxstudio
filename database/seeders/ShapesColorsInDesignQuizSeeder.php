<?php

namespace Database\Seeders;

use App\Models\GradeLevel;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShapesColorsInDesignQuizSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::where('email', 'angelika@edupulse.com')->first()
            ?? User::where('role', 'teacher')->first();

        $gradeLevel = GradeLevel::where('name', 'Grade 10')->orderBy('id')->first();
        $sections = Section::where('grade_level_id', $gradeLevel?->id)->get();

        $quizTitle = 'Shapes, Colors, and Graphic Elements in Adobe InDesign';
        $quizDescription = 'Read each question carefully. Choose the best answer. Some choices may have similar functions, so analyze the specific situation before answering.';

        $quiz = Quiz::updateOrCreate(
            ['title' => $quizTitle],
            [
                'teacher_id' => $teacher?->id ?? 4,
                'description' => $quizDescription,
                'grade' => 'Grade 10',
                'time_limit_minutes' => 30,
                'is_published' => true,
                'closes_at' => null,
            ]
        );

        if ($gradeLevel) {
            $quiz->gradeLevels()->syncWithoutDetaching([$gradeLevel->id]);
        }

        if ($sections->isNotEmpty()) {
            $quiz->sections()->syncWithoutDetaching($sections->pluck('id')->all());
        }

        $questionsData = [
            [
                'question_text' => 'A student wants to move an already-created rectangle to another location without changing its shape. Which tool should be used?',
                'options' => [
                    ['text' => 'Rectangle Tool', 'is_correct' => false],
                    ['text' => 'Selection Tool', 'is_correct' => true],
                    ['text' => 'Free Transform Tool', 'is_correct' => false],
                    ['text' => 'Direct Selection Tool', 'is_correct' => false],
                ],
            ],
            [
                'question_text' => 'A rectangle has a blue interior and a thin black outline. Which statement correctly identifies these properties?',
                'options' => [
                    ['text' => 'Blue is the stroke, while black is the fill.', 'is_correct' => false],
                    ['text' => 'Blue is the fill, while black is the stroke.', 'is_correct' => true],
                    ['text' => 'Blue is the gradient, while black is the swatch.', 'is_correct' => false],
                    ['text' => 'Blue is the swatch, while black is the transparency.', 'is_correct' => false],
                ],
            ],
            [
                'question_text' => 'A designer wants to create a circular graphic element. Which tool is most appropriate?',
                'options' => [
                    ['text' => 'Polygon Tool', 'is_correct' => false],
                    ['text' => 'Rectangle Tool', 'is_correct' => false],
                    ['text' => 'Ellipse Tool', 'is_correct' => true],
                    ['text' => 'Selection Tool', 'is_correct' => false],
                ],
            ],
            [
                'question_text' => 'A student changes only the color inside a shape while keeping its border unchanged. Which property was modified?',
                'options' => [
                    ['text' => 'Stroke', 'is_correct' => false],
                    ['text' => 'Fill', 'is_correct' => true],
                    ['text' => 'Transparency', 'is_correct' => false],
                    ['text' => 'Gradient', 'is_correct' => false],
                ],
            ],
            [
                'question_text' => 'A designer wants a smooth transition from dark blue to light blue instead of using one solid color. Which feature should be used?',
                'options' => [
                    ['text' => 'Stroke', 'is_correct' => false],
                    ['text' => 'Swatch', 'is_correct' => false],
                    ['text' => 'Gradient', 'is_correct' => true],
                    ['text' => 'Transparency', 'is_correct' => false],
                ],
            ],
            [
                'question_text' => 'Two shapes overlap, and the designer makes the top shape partly see-through so the shape underneath can be seen. What property is being adjusted?',
                'options' => [
                    ['text' => 'Gradient', 'is_correct' => false],
                    ['text' => 'Transparency', 'is_correct' => true],
                    ['text' => 'Stroke', 'is_correct' => false],
                    ['text' => 'Fill', 'is_correct' => false],
                ],
            ],
            [
                'question_text' => 'A designer wants to save and reuse the same set of colors throughout a publication. Which feature is most appropriate?',
                'options' => [
                    ['text' => 'Swatches', 'is_correct' => true],
                    ['text' => 'Gradients', 'is_correct' => false],
                    ['text' => 'Strokes', 'is_correct' => false],
                    ['text' => 'Transparency', 'is_correct' => false],
                ],
            ],
            [
                'question_text' => 'Which situation best demonstrates the purpose of using shapes as graphic elements?',
                'options' => [
                    ['text' => 'Using a rectangle to highlight an important section of text', 'is_correct' => true],
                    ['text' => 'Using a rectangle to replace all written information', 'is_correct' => false],
                    ['text' => 'Using random shapes to fill every empty space', 'is_correct' => false],
                    ['text' => 'Using shapes only to increase the number of pages', 'is_correct' => false],
                ],
            ],
            [
                'question_text' => 'A publication uses a colored box behind a heading and a different colored box behind an important announcement. What should the designer consider most when choosing the colors?',
                'options' => [
                    ['text' => 'Consistency and readability', 'is_correct' => true],
                    ['text' => 'Maximum number of colors', 'is_correct' => false],
                    ['text' => 'Random color selection', 'is_correct' => false],
                    ['text' => 'The number of shapes used', 'is_correct' => false],
                ],
            ],
            [
                'question_text' => 'A student uses five bright colors, several unrelated shapes, and different visual styles throughout one page. What design problem is most likely present?',
                'options' => [
                    ['text' => 'Excessive consistency', 'is_correct' => false],
                    ['text' => 'Lack of visual consistency', 'is_correct' => true],
                    ['text' => 'Insufficient graphic elements', 'is_correct' => false],
                    ['text' => 'Lack of document pages', 'is_correct' => false],
                ],
            ],
            [
                'question_text' => 'Which combination correctly matches the design feature with its function?',
                'options' => [
                    ['text' => 'Fill – controls the outline of a shape', 'is_correct' => false],
                    ['text' => 'Stroke – controls the inside color of a shape', 'is_correct' => false],
                    ['text' => 'Gradient – creates a gradual color transition', 'is_correct' => true],
                    ['text' => 'Transparency – changes the number of sides of a shape', 'is_correct' => false],
                ],
            ],
            [
                'question_text' => 'A designer places a semi-transparent colored rectangle behind text. Why might this be useful?',
                'options' => [
                    ['text' => 'It can emphasize the text while allowing background elements to remain partially visible.', 'is_correct' => true],
                    ['text' => 'It automatically changes the font size of the text.', 'is_correct' => false],
                    ['text' => 'It removes the need to format the text.', 'is_correct' => false],
                    ['text' => 'It automatically creates additional pages.', 'is_correct' => false],
                ],
            ],
            [
                'question_text' => 'A student wants to create a triangular graphic element using a shape tool. Which tool would be most appropriate?',
                'options' => [
                    ['text' => 'Ellipse Tool', 'is_correct' => false],
                    ['text' => 'Polygon Tool', 'is_correct' => true],
                    ['text' => 'Rectangle Tool', 'is_correct' => false],
                    ['text' => 'Selection Tool', 'is_correct' => false],
                ],
            ],
            [
                'question_text' => 'A designer is creating a school publication and wants its visual elements to look unified. Which approach is most appropriate?',
                'options' => [
                    ['text' => 'Use unrelated colors for every element.', 'is_correct' => false],
                    ['text' => 'Use consistent colors, shapes, and visual styles throughout the layout.', 'is_correct' => true],
                    ['text' => 'Add as many shapes as possible to every section.', 'is_correct' => false],
                    ['text' => 'Use different design styles on every page.', 'is_correct' => false],
                ],
            ],
            [
                'question_text' => 'A colored shape is placed behind important text, but the text becomes difficult to read. What should the designer consider first?',
                'options' => [
                    ['text' => 'Adding more shapes', 'is_correct' => false],
                    ['text' => 'Increasing the number of colors', 'is_correct' => false],
                    ['text' => 'Adjusting the color or transparency to improve readability', 'is_correct' => true],
                    ['text' => 'Removing all graphic elements from the page', 'is_correct' => false],
                ],
            ],
        ];

        // Ensure questions are synced cleanly
        $existingQuestions = $quiz->questions()->get();
        if ($existingQuestions->count() !== count($questionsData)) {
            foreach ($existingQuestions as $eq) {
                $eq->options()->delete();
                $eq->delete();
            }

            foreach ($questionsData as $index => $qData) {
                $question = $quiz->questions()->create([
                    'question_text' => $qData['question_text'],
                    'type' => 'multiple_choice',
                    'points' => 1,
                    'order' => $index,
                ]);

                foreach ($qData['options'] as $oIndex => $optData) {
                    $question->options()->create([
                        'option_text' => $optData['text'],
                        'is_correct' => $optData['is_correct'],
                        'order' => $oIndex,
                    ]);
                }
            }
        }

        $this->exportSeedData();
    }

    protected function exportSeedData(): void
    {
        $tables = [
            'quizzes',
            'quiz_grade_level',
            'quiz_section',
            'questions',
            'quiz_options',
        ];

        $dataDir = database_path('seeders/data');
        if (! is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        foreach ($tables as $table) {
            $rows = DB::table($table)->orderBy('id')->get()->map(function ($row) {
                return (array) $row;
            })->toArray();

            $jsonPath = $dataDir . DIRECTORY_SEPARATOR . "{$table}.json";
            file_put_contents($jsonPath, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
}
