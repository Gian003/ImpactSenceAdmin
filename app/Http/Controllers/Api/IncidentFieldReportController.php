<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\IncidentFieldPhoto;
use App\Models\IncidentFieldReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class IncidentFieldReportController extends Controller
{
    /** Ten frames is more than a roadside scene needs and well under PHP's limits. */
    private const MAX_PHOTOS = 10;

    /**
     * Files a responding unit's account of the scene.
     *
     * Creates a new report every time rather than updating an existing one:
     * these are append-only, so a follow-up is a supplemental report and the
     * sequence of what was said survives.
     */
    public function store(Request $request, Incident $incident): JsonResponse
    {
        $patrol = $request->user();

        $data = $request->validate([
            'narrative'         => ['nullable', 'string', 'max:5000'],
            'vehicles_involved' => ['nullable', 'integer', 'min:0', 'max:255'],
            'injured_count'     => ['nullable', 'integer', 'min:0', 'max:255'],
            'road_condition'    => ['nullable', Rule::in(IncidentFieldReport::ROAD_CONDITIONS)],
            'weather_condition' => ['nullable', Rule::in(IncidentFieldReport::WEATHER_CONDITIONS)],
            'latitude'          => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'         => ['nullable', 'numeric', 'between:-180,180'],
            'photos'            => ['nullable', 'array', 'max:' . self::MAX_PHOTOS],
            'photos.*'          => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'], // 8MB each
        ]);

        // A report with nothing in it is not a report. Guarding here rather
        // than with 'required' rules keeps every individual field optional —
        // a responder who only has photos, or only has a narrative, should
        // still be able to file.
        $hasContent = trim((string) ($data['narrative'] ?? '')) !== ''
            || $request->hasFile('photos')
            || collect(IncidentFieldReport::CONDITION_FIELDS)
                ->contains(fn ($f) => ($data[$f] ?? null) !== null);

        if (! $hasContent) {
            return $this->apiResponse(false, 'A field report needs a narrative, a condition or at least one photo.', null, 422);
        }

        // Written before the transaction: files that land on disk but never
        // get a row are recoverable clutter, whereas rows pointing at files
        // that were rolled away are broken evidence.
        $stored = $this->storePhotos($request, $incident);

        try {
            $report = DB::transaction(function () use ($data, $incident, $patrol, $stored) {
                $report = IncidentFieldReport::create([
                    'incident_id'         => $incident->id,
                    'patrol_unit_id'      => $patrol?->id,
                    'narrative'           => $data['narrative'] ?? null,
                    'vehicles_involved'   => $data['vehicles_involved'] ?? null,
                    'injured_count'       => $data['injured_count'] ?? null,
                    'road_condition'      => $data['road_condition'] ?? null,
                    'weather_condition'   => $data['weather_condition'] ?? null,
                    'submitted_latitude'  => $data['latitude'] ?? null,
                    'submitted_longitude' => $data['longitude'] ?? null,
                    'submitted_at'        => now(),
                ]);

                foreach ($stored as $photo) {
                    $report->photos()->create($photo);
                }

                $this->backfillIncidentConditions($incident, $report);

                return $report;
            });
        } catch (\Throwable $e) {
            // The rows are gone; the files must go too, or the private disk
            // accumulates orphaned photographs of crash victims.
            foreach ($stored as $photo) {
                Storage::disk(IncidentFieldPhoto::DISK)->delete($photo['path']);
            }

            Log::error('Field report submission failed', [
                'incident_id' => $incident->id,
                'error'       => $e->getMessage(),
            ]);

            return $this->apiResponse(false, 'Could not save the field report. Please try again.', null, 500);
        }

        $report->load('photos');

        \App\Models\IncidentEvent::record(
            $incident,
            \App\Models\IncidentEvent::FIELD_REPORT_FILED,
            ['payload' => [
                'field_report_id' => $report->id,
                'photo_count'     => $report->photos->count(),
            ]],
            $patrol,
        );

        return $this->apiResponse(true, 'Field report submitted', [
            'id'           => $report->id,
            'submitted_at' => $report->submitted_at->toIso8601String(),
            'photo_count'  => $report->photos->count(),
        ]);
    }

    /**
     * Moves the uploads onto the private disk, recording the metadata that
     * makes a file evidence rather than just an attachment.
     *
     * @return array<int, array<string, mixed>>
     */
    private function storePhotos(Request $request, Incident $incident): array
    {
        $stored = [];

        foreach ($request->file('photos', []) as $file) {
            $path = $file->store("incident-field-photos/{$incident->id}", IncidentFieldPhoto::DISK);

            $stored[] = [
                'path'              => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type'         => $file->getClientMimeType(),
                'size_bytes'        => Storage::disk(IncidentFieldPhoto::DISK)->size($path),
                'sha256'            => hash('sha256', Storage::disk(IncidentFieldPhoto::DISK)->get($path)),
                // When the shutter fired, not when the upload arrived — a
                // responder with no signal may file hours later.
                'captured_at'       => $request->date('captured_at') ?? now(),
            ];
        }

        return $stored;
    }

    /**
     * Copies the four scene conditions onto the incident, which is what the
     * IRF form actually prefills from.
     *
     * Only fills blanks. If investigation staff have already entered a figure
     * on the IRF page, a later supplemental report must not silently rewrite
     * it underneath them — the incident row is the investigator's working
     * copy, while the report row keeps the responder's original either way.
     */
    private function backfillIncidentConditions(Incident $incident, IncidentFieldReport $report): void
    {
        $updates = [];

        foreach (IncidentFieldReport::CONDITION_FIELDS as $field) {
            if ($incident->{$field} === null && $report->{$field} !== null) {
                $updates[$field] = $report->{$field};
            }
        }

        if ($updates) {
            $incident->update($updates);
        }
    }
}
