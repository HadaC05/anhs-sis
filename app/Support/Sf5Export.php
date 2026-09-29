<?php

namespace App\Support;

use App\Models\SchoolInformation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class Sf5Export
{
    public static function download(Collection $enrollments): BinaryFileResponse
    {
        if ($enrollments->isEmpty()) {
            throw ValidationException::withMessages(['sf5' => 'No learners match the selected filters.']);
        }
        $enrollments->loadMissing(['section.academicYear', 'section.gradeLevel', 'section.adviser', 'section.curriculum']);
        if ($enrollments->contains(fn ($row) => ! $row->section || (int) $row->SY_ID !== (int) $row->section->SY_ID)) {
            throw ValidationException::withMessages(['sf5' => 'Every filtered learner must have a section in their school year to generate SF 5. Narrow the filters or complete their section assignments.']);
        }
        $school = SchoolInformation::current();
        $principal = Sf9ReportCardBuilder::principalName();
        $files = [];
        $archivePath = null;
        try {
            foreach ($enrollments->groupBy('section_ID') as $group) {
                $section = $group->first()->section;
                $path = Sf5Workbook::create(Sf5ReportBuilder::rows($section, $group), [
                    'C3' => $school->region ?? '',
                    'E3' => $school->division ?? '',
                    'J3' => $school->district ?? '',
                    'C5' => $school->school_id ?? '',
                    'G5' => $section->academicYear?->school_year ?? '',
                    'J5' => $section->curriculum?->name ?? '',
                    'C7' => $school->name ?? '',
                    'J7' => $section->getRelation('gradeLevel')?->grade_label ?? '',
                    'M7' => $section->name,
                    'L36' => trim(($section->adviser?->first_name ?? '').' '.($section->adviser?->last_name ?? '')),
                    'L41' => $principal,
                ]);
                $files[] = ['path' => $path, 'name' => 'SF5-'.Str::slug($section->name.'-'.$section->academicYear?->school_year), 'id' => $section->section_ID];
            }
            if (count($files) === 1) {
                return response()->download($files[0]['path'], $files[0]['name'].'.xlsx', [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Cache-Control' => 'private, no-store',
                ])->deleteFileAfterSend(true);
            }
            $archivePath = tempnam(sys_get_temp_dir(), 'sf5_export_');
            $zip = new ZipArchive;
            if ($archivePath === false || $zip->open($archivePath, ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Unable to create SF5 archive.');
            }
            foreach ($files as $file) {
                if (! $zip->addFile($file['path'], $file['name'].'-'.$file['id'].'.xlsx')) {
                    throw new \RuntimeException('Unable to add SF5 workbook to archive.');
                }
            }
            if (! $zip->close()) {
                throw new \RuntimeException('Unable to finish SF5 archive.');
            }
            foreach ($files as $file) {
                @unlink($file['path']);
            }

            return response()->download($archivePath, 'SF5-filtered-results.zip', [
                'Content-Type' => 'application/zip', 'Cache-Control' => 'private, no-store',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $exception) {
            foreach ($files as $file) {
                @unlink($file['path']);
            }
            if ($archivePath) {
                @unlink($archivePath);
            }
            throw $exception;
        }
    }
}
