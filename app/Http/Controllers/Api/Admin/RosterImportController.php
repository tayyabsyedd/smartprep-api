<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstituteRoster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RosterImportController extends Controller
{
    /**
     * POST /api/admin/roster/import (admin only)
     * Accepts a CSV with roll_id + student_name columns (program/batch optional).
     * Batch upsert: existing rows get fresh names, but is_claimed and
     * claimed_by are never overwritten — claimed roll IDs stay claimed.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');

        $headers = null;
        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                $line[0] = preg_replace('/^\xEF\xBB\xBF/', '', $line[0] ?? '');
                $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $line);

                if (! in_array('roll_id', $headers, true)) {
                    fclose($handle);

                    return response()->json(
                        ['message' => "CSV must contain a 'roll_id' column."],
                        422
                    );
                }

                continue;
            }

            if (count(array_filter($line, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $row = array_combine($headers, array_pad($line, count($headers), null));
            $rollId = trim((string) ($row['roll_id'] ?? ''));

            if ($rollId === '') {
                continue;
            }

            // Keyed by roll_id: duplicates inside the file itself collapse (last wins).
            $rows[$rollId] = [
                'enrollment_id' => $rollId,
                'student_name' => trim((string) ($row['student_name'] ?? '')) ?: null,
                'program' => trim((string) ($row['program'] ?? '')) ?: null,
                'batch' => trim((string) ($row['batch'] ?? '')) ?: null,
            ];
        }

        fclose($handle);

        if (empty($rows)) {
            return response()->json(['message' => 'No valid rows found in the CSV.'], 422);
        }

        $existing = InstituteRoster::whereIn('enrollment_id', array_keys($rows))
            ->pluck('enrollment_id')
            ->all();

        $now = now();
        $upsertRows = array_map(
            fn ($row) => $row + ['created_at' => $now, 'updated_at' => $now],
            array_values($rows)
        );

        DB::transaction(function () use ($upsertRows) {
            InstituteRoster::upsert(
                $upsertRows,
                ['enrollment_id'],
                ['student_name', 'program', 'batch', 'updated_at']
            );
        });

        return response()->json([
            'message' => 'Roster imported successfully.',
            'imported' => count($rows) - count($existing),
            'duplicates_skipped' => count($existing),
        ]);
    }
}
