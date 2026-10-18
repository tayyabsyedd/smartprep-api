<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    /**
     * GET /api/documents — structured Subject -> Module -> Chapter list.
     * Public documents are visible to everyone (including guests);
     * 'enrolled' documents only to enrolled students.
     */
    public function index(Request $request)
    {
        // Optional auth: guests may browse public documents, so we resolve
        // the sanctum user manually instead of requiring auth middleware.
        // NOTE: enrolled documents are NOT filtered out of the listing.
        // Guests and non-enrolled students see them with a lock badge (the
        // `visibility` flag); the stream endpoint stays the enforcement
        // point and still 403s them.
        $query = Document::with(['subject', 'module'])->orderBy('id');

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->integer('subject_id'));
        }

        if ($request->filled('module_id')) {
            $query->where('module_id', $request->integer('module_id'));
        }

        $subjects = $query->get()
            ->groupBy('subject_id')
            ->map(function ($docs) {
                $subject = $docs->first()->subject;

                $modules = $docs->groupBy('module_id')->map(function ($moduleDocs) {
                    $module = $moduleDocs->first()->module;

                    $chapters = $moduleDocs->groupBy('chapter')->map(fn ($chapterDocs, $chapter) => [
                        'chapter' => $chapter,
                        'documents' => $chapterDocs->map(fn ($doc) => [
                            'id' => $doc->id,
                            'title' => $doc->title,
                            'visibility' => $doc->visibility,
                        ])->values(),
                    ])->values();

                    return [
                        'id' => $module?->id,
                        'name' => $module?->name ?? 'General',
                        'sort_order' => $module?->sort_order ?? 0,
                        'chapters' => $chapters,
                    ];
                })->sortBy('sort_order')->values();

                return [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'sort_order' => $subject->sort_order ?? 0,
                    'modules' => $modules,
                ];
            })
            ->sortBy('sort_order')
            ->values();

        return response()->json(['subjects' => $subjects]);
    }

    /**
     * GET /api/documents/{document}/stream — gatekept PDF streaming.
     *
     * Files live under storage/app/notes, which is never web-accessible,
     * so this route is the only way to reach them (no hotlinking possible).
     */
    public function stream(Request $request, Document $document)
    {
        // Optional auth (see index()): enrolled-only docs need a logged-in
        // enrolled student; public docs stream for everyone.
        $user = auth('sanctum')->user();

        if ($document->visibility === 'enrolled' && ! ($user && $user->is_enrolled)) {
            abort(403, 'This document is available to enrolled students only.');
        }

        $base = realpath(storage_path('app/notes'));

        if ($base === false) {
            abort(404, 'File not found.');
        }

        // realpath() + prefix check neutralizes any path traversal in file_path.
        $absolute = realpath(storage_path('app/'.$document->file_path));

        if ($absolute === false || ! str_starts_with($absolute, $base) || ! is_file($absolute)) {
            abort(404, 'File not found.');
        }

        return response()->file($absolute, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.basename($absolute).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
