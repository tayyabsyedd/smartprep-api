<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\Module;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        // Private notes store. Note: Storage::disk('local') points at
        // storage/app/private in Laravel 11+, so create the notes dir directly.
        @mkdir(storage_path('app/notes'), 0755, true);

        $subject = Subject::where('slug', 'physics')->firstOrFail();
        $mechanics = Module::where('subject_id', $subject->id)
            ->where('name', 'Mechanics')
            ->firstOrFail();

        $docs = [
            [
                'chapter' => 'Kinematics',
                'title' => 'Kinematics — Formula Sheet',
                'file_path' => 'notes/mechanics-kinematics.pdf',
                'visibility' => 'public',
            ],
            [
                'chapter' => 'Dynamics',
                'title' => "Newton's Laws — Worked Examples",
                'file_path' => 'notes/mechanics-dynamics.pdf',
                'visibility' => 'enrolled',
            ],
        ];

        foreach ($docs as $index => $doc) {
            Document::firstOrCreate(
                ['file_path' => $doc['file_path']],
                $doc + [
                    'subject_id' => $subject->id,
                    'module_id' => $mechanics->id,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }
}
