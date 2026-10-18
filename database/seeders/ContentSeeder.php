<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Test;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $subject = Subject::firstOrCreate(
            ['slug' => 'physics'],
            ['name' => 'Physics', 'sort_order' => 1]
        );

        $mechanics = Module::firstOrCreate(
            ['subject_id' => $subject->id, 'name' => 'Mechanics'],
            ['description' => 'Motion, forces, work and energy.', 'sort_order' => 1]
        );

        $electromagnetism = Module::firstOrCreate(
            ['subject_id' => $subject->id, 'name' => 'Electromagnetism'],
            ['description' => 'Charge, circuits and magnetism.', 'sort_order' => 2]
        );

        $this->seedTest($mechanics, [
            'title' => 'Mechanics Basics',
            'description' => 'Warm-up practice set: forces and motion.',
            'kind' => 'practice',
            'timed' => false,
            'duration_minutes' => null,
            'is_free' => true,
            'price' => null,
            'status' => 'published',
        ], [
            [
                'qtype' => 'mcq',
                'question_text' => 'What is the SI unit of force?',
                'options' => ['Newton', 'Joule', 'Watt', 'Pascal'],
                'correct_options' => [0],
                'explanation' => 'Force is measured in Newtons (N) in the SI system.',
            ],
            [
                'qtype' => 'mcq',
                'question_text' => 'Which law is expressed as F = ma?',
                'options' => ["Newton's First Law", "Newton's Second Law", "Newton's Third Law", "Hooke's Law"],
                'correct_options' => [1],
                'explanation' => "Newton's Second Law relates force, mass and acceleration.",
            ],
            [
                'qtype' => 'true_false',
                'question_text' => 'Velocity is a scalar quantity.',
                'options' => ['True', 'False'],
                'correct_options' => [1],
                'explanation' => 'Velocity has both magnitude and direction, so it is a vector.',
            ],
            [
                'qtype' => 'mcq',
                'question_text' => 'A car accelerates from rest at 2 m/s² for 5 s. Its final velocity is:',
                'options' => ['10 m/s', '5 m/s', '2 m/s', '25 m/s'],
                'correct_options' => [0],
                'explanation' => 'v = u + at = 0 + 2 × 5 = 10 m/s.',
            ],
            [
                'qtype' => 'multi_select',
                'question_text' => 'Which of the following are vector quantities?',
                'options' => ['Displacement', 'Speed', 'Acceleration', 'Distance'],
                'correct_options' => [0, 2],
                'explanation' => 'Displacement and acceleration have direction; speed and distance do not.',
            ],
        ]);

        $this->seedTest($mechanics, [
            'title' => 'Mechanics Mock Exam',
            'description' => 'Timed mock: work, energy and momentum.',
            'kind' => 'mock',
            'timed' => true,
            'duration_minutes' => 60,
            'is_free' => false,
            'price' => 499.00,
            'status' => 'published',
        ], [
            [
                'qtype' => 'mcq',
                'question_text' => 'Work done when a 10 N force moves an object 5 m in the direction of the force:',
                'options' => ['50 J', '2 J', '15 J', '0 J'],
                'correct_options' => [0],
                'explanation' => 'W = F × d = 10 × 5 = 50 J.',
            ],
            [
                'qtype' => 'true_false',
                'question_text' => 'Kinetic energy of a moving object can be negative.',
                'options' => ['True', 'False'],
                'correct_options' => [1],
                'explanation' => 'KE = ½mv² is always zero or positive.',
            ],
            [
                'qtype' => 'mcq',
                'question_text' => 'Power is defined as:',
                'options' => ['Work / Time', 'Force × Distance', 'Mass × Velocity', 'Energy × Time'],
                'correct_options' => [0],
                'explanation' => 'Power is the rate of doing work.',
            ],
            [
                'qtype' => 'multi_select',
                'question_text' => 'Which of the following are forms of energy?',
                'options' => ['Kinetic', 'Potential', 'Thermal', 'Velocity'],
                'correct_options' => [0, 1, 2],
                'explanation' => 'Velocity is not a form of energy.',
            ],
            [
                'qtype' => 'mcq',
                'question_text' => 'Momentum of a 2 kg object moving at 3 m/s:',
                'options' => ['6 kg·m/s', '5 kg·m/s', '1.5 kg·m/s', '9 kg·m/s'],
                'correct_options' => [0],
                'explanation' => 'p = mv = 2 × 3 = 6 kg·m/s.',
            ],
        ]);

        $this->seedTest($electromagnetism, [
            'title' => 'Electromagnetism Basics',
            'description' => 'Warm-up practice set: charge and circuits.',
            'kind' => 'practice',
            'timed' => false,
            'duration_minutes' => null,
            'is_free' => true,
            'price' => null,
            'status' => 'published',
        ], [
            [
                'qtype' => 'mcq',
                'question_text' => 'What is the SI unit of electric charge?',
                'options' => ['Coulomb', 'Ampere', 'Volt', 'Ohm'],
                'correct_options' => [0],
                'explanation' => 'Charge is measured in Coulombs (C).',
            ],
            [
                'qtype' => 'true_false',
                'question_text' => 'Like charges attract each other.',
                'options' => ['True', 'False'],
                'correct_options' => [1],
                'explanation' => 'Like charges repel; opposite charges attract.',
            ],
            [
                'qtype' => 'mcq',
                'question_text' => "Ohm's law is expressed as:",
                'options' => ['V = IR', 'P = VI', 'F = ma', 'E = mc²'],
                'correct_options' => [0],
                'explanation' => 'Voltage equals current times resistance.',
            ],
            [
                'qtype' => 'multi_select',
                'question_text' => 'Which of these materials are electrical conductors?',
                'options' => ['Copper', 'Rubber', 'Aluminum', 'Glass'],
                'correct_options' => [0, 2],
                'explanation' => 'Copper and aluminum conduct; rubber and glass insulate.',
            ],
            [
                'qtype' => 'mcq',
                'question_text' => 'The magnetic field around a straight current-carrying wire is:',
                'options' => ['Circular', 'Straight', 'Zero', 'Elliptical'],
                'correct_options' => [0],
                'explanation' => 'Field lines form concentric circles around the wire.',
            ],
        ]);
    }

    protected function seedTest(Module $module, array $testAttrs, array $questions): void
    {
        $test = Test::firstOrCreate(
            ['module_id' => $module->id, 'title' => $testAttrs['title']],
            $testAttrs
        );

        if ($test->questions()->exists()) {
            return;
        }

        foreach ($questions as $index => $question) {
            Question::create(array_merge($question, [
                'test_id' => $test->id,
                'marks' => 1,
                'sort_order' => $index + 1,
            ]));
        }
    }
}
