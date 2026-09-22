<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherSubjectAssignment extends Model
{
    use HasFactory;

    protected $table = 'teacher_subject_assignments';

    protected $primaryKey = 'assignment_ID';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'section_ID',
        'curr_subj_ID',
        'staff_ID',
        'SY_ID',
    ];

    public function getRouteKeyName(): string
    {
        return 'assignment_ID';
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_ID', 'section_ID');
    }

    public function curriculumSubject(): BelongsTo
    {
        return $this->belongsTo(CurriculumSubject::class, 'curr_subj_ID', 'curr_subj_ID');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_ID', 'staff_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'SY_ID', 'SY_ID');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(StudentSubjectGrade::class, 'assignment_ID', 'assignment_ID');
    }

    /**
     * @param  Builder<TeacherSubjectAssignment>  $query
     * @return Builder<TeacherSubjectAssignment>
     */
    public function scopeWithGradeStatusCounts(Builder $query): Builder
    {
        $query->withCount('grades');

        foreach (GradeStatus::idsBySlug() as $status => $statusId) {
            $query->withCount([
                "grades as {$status}_grades_count" => fn ($gradeQuery) => $gradeQuery->where('grade_status_ID', $statusId),
            ]);
        }

        return $query;
    }

    public function gradeProgressLabel(): string
    {
        if ((int) ($this->grades_count ?? 0) === 0) {
            return 'Ungraded';
        }

        $presentStatuses = collect(GradeStatus::slugs())
            ->filter(fn (string $status): bool => (int) ($this->{"{$status}_grades_count"} ?? 0) > 0)
            ->values();

        if ($presentStatuses->count() === 1) {
            return GradeStatus::nameFor($presentStatuses->first());
        }

        return 'In progress';
    }

    /**
     * @return array<string, int>
     */
    public function gradeStatusCounts(): array
    {
        $counts = [];

        foreach (GradeStatus::slugs() as $status) {
            $count = (int) ($this->{"{$status}_grades_count"} ?? 0);

            if ($count > 0) {
                $counts[$status] = $count;
            }
        }

        return $counts;
    }
}
