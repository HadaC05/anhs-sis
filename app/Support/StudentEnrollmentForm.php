<?php

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\Cluster;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\LearnerType;
use App\Models\PreferredCourse;
use App\Models\Religion;
use App\Models\Student;
use App\Models\StudentApplication;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StudentEnrollmentForm
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function viewData(array $overrides = []): array
    {
        return array_merge([
            'layout' => 'layouts.public-enrollment',
            'pageTitle' => 'Registration',
            'heading' => 'Student Registration',
            'subheading' => 'Complete the enrollment application. You will be temporarily enrolled so you can sign in and submit the remaining documents.',
            'enrollmentStoreRoute' => 'register.store',
            'enrollmentStoreUrl' => null,
            'enrollmentMethod' => null,
            'checkLrnRoute' => route('register.check-lrn'),
            'checkEmailRoute' => route('register.check-email'),
            'cancelUrl' => route('home'),
            'submitLabel' => 'Submit Enrollment',
            'student' => null,
            'application' => null,
            'formDefaults' => [],
            'ignoreStudentId' => null,
            'fromSectionId' => null,
            'clusters' => Cluster::query()
                ->with(['preferredCourses' => fn ($query) => $query->orderBy('name')])
                ->orderBy('name')
                ->get(['cluster_ID', 'name']),
            'gradeLevels' => GradeLevel::options(),
            'suffixOptions' => StudentApplication::suffixOptions(),
            'earliestBirthdate' => StudentApplication::EARLIEST_BIRTHDATE,
            'religions' => Religion::options(),
            'activeYear' => AcademicYear::query()->where('status', true)->first(),
            'hasEnrollment' => false,
            'currentEnrollment' => null,
        ], $overrides);
    }

    public static function prepare(Request $request): void
    {
        self::mergeLastSchoolYearCompleted($request);

        $request->merge(CapitalizesFormText::apply($request->all()));
        $request->merge([
            'father_is_deceased' => $request->boolean('father_is_deceased'),
            'mother_is_deceased' => $request->boolean('mother_is_deceased'),
            'guardian_is_deceased' => $request->boolean('guardian_is_deceased'),
        ]);

        $normalizedLearnerType = LearnerType::normalizeSlug($request->input('learner_type'));

        if ($normalizedLearnerType !== null) {
            $request->merge(['learner_type' => $normalizedLearnerType]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(?int $ignoreStudentId = null): array
    {
        return [
            'grade_level' => ['required', 'in:7,8,9,10,11,12'],
            'LRN' => ['required', 'digits:12', Rule::unique('students', 'lrn')->ignore($ignoreStudentId)],
            'semester' => ['nullable', 'in:first,second'],
            'cluster_ID' => ['nullable', 'integer', 'exists:clusters,cluster_ID'],
            'course_ID' => ['nullable', 'integer', 'exists:preferred_courses,course_ID'],
            'learner_type' => ['required', Rule::in(LearnerType::slugs())],
            'last_grade_level_completed' => ['nullable', 'in:6,7,8,9,10,11,12'],
            'last_school_year_completed' => ['nullable', 'regex:/^\d{4}-\d{4}$/'],
            // Kept for API clients and previously saved form drafts that still submit the old two-input format.
            'last_school_year_completed_start' => ['nullable', 'digits:4'],
            'last_school_year_completed_end' => ['nullable', 'digits:4'],
            'last_school_attended' => ['required', 'string', 'max:255'],
            'school_id_from_previous_school' => ['nullable', 'digits:6'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', Rule::in(StudentApplication::suffixOptions())],
            'birthdate' => ['required', 'date', 'after_or_equal:'.StudentApplication::EARLIEST_BIRTHDATE],
            'birthplace' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:Male,Female'],
            'contact_no' => ['required', 'regex:/^\+63\d{10}$/'],
            'email' => ['required', 'email', 'max:255', Rule::unique('students', 'email')->ignore($ignoreStudentId)],
            'religion' => ['required', 'string', Rule::exists('religions', 'name')],
            'mother_tongue' => ['required', 'string', 'max:255'],
            'ip_community' => ['required', 'in:No,Yes'],
            'ip_details' => ['nullable', 'string', 'max:255'],
            'four_ps_beneficiary' => ['required', 'in:No,Yes'],
            'four_ps_details' => [
                Rule::requiredIf(fn (): bool => request()->input('four_ps_beneficiary') === 'Yes'),
                'nullable',
                'string',
                'min:17',
                'max:21',
            ],
            'pwd' => ['required', 'in:No,Yes'],
            'pwd_details' => ['nullable', 'string', 'max:255'],
            'curr_house_no' => ['nullable', 'string', 'max:255'],
            'curr_street_name' => ['nullable', 'string', 'max:255'],
            'curr_barangay' => ['required', 'string', 'max:255'],
            'curr_municipality_city' => ['required', 'string', 'max:255'],
            'curr_province' => ['required', 'string', 'max:255'],
            'curr_country' => ['nullable', 'string', 'max:255'],
            'curr_zip_code' => ['required', 'string', 'max:255'],
            'perm_house_no' => ['nullable', 'string', 'max:255'],
            'perm_street_name' => ['nullable', 'string', 'max:255'],
            'perm_barangay' => ['required', 'string', 'max:255'],
            'perm_municipality_city' => ['required', 'string', 'max:255'],
            'perm_province' => ['required', 'string', 'max:255'],
            'perm_country' => ['nullable', 'string', 'max:255'],
            'perm_zip_code' => ['required', 'string', 'max:255'],
            'father_lname' => ['required', 'string', 'max:255'],
            'father_fname' => ['required', 'string', 'max:255'],
            'father_mname' => ['nullable', 'string', 'max:255'],
            'father_suffix' => ['nullable', 'string', Rule::in(StudentApplication::suffixOptions())],
            'father_contact_no' => ['nullable', 'regex:/^\+63\d{10}$/'],
            'mother_lname' => ['required', 'string', 'max:255'],
            'mother_fname' => ['required', 'string', 'max:255'],
            'mother_mname' => ['nullable', 'string', 'max:255'],
            'mother_suffix' => ['nullable', 'string', Rule::in(StudentApplication::suffixOptions())],
            'mother_contact_no' => ['nullable', 'regex:/^\+63\d{10}$/'],
            'guardian_lname' => ['nullable', 'string', 'max:255'],
            'guardian_fname' => ['nullable', 'string', 'max:255'],
            'guardian_mname' => ['nullable', 'string', 'max:255'],
            'guardian_suffix' => ['nullable', 'string', Rule::in(StudentApplication::suffixOptions())],
            'guardian_contact_no' => ['nullable', 'regex:/^\+63\d{10}$/'],
            'father_is_deceased' => ['required', 'boolean'],
            'mother_is_deceased' => ['required', 'boolean'],
            'guardian_is_deceased' => ['required', 'boolean'],
            'same_address' => ['nullable', 'boolean'],
            'from_section' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'birthdate.after_or_equal' => 'The birthdate must be on or after January 1, 1950.',
            'four_ps_details.required' => 'Please provide the 4Ps Household ID Number.',
            'four_ps_details.min' => 'The 4Ps Household ID Number must be at least 17 characters.',
            'four_ps_details.max' => 'The 4Ps Household ID Number must not be greater than 21 characters.',
        ];
    }

    public static function applyAfterValidation(Validator $validator, ?AcademicYear $academicYear, bool $requireActiveYear = true): void
    {
        RejectsUnsafeFormCharacters::apply($validator);

        $validator->after(function (Validator $validator) use ($academicYear, $requireActiveYear): void {
            $data = $validator->getData();

            if ($requireActiveYear && ! $academicYear) {
                $validator->errors()->add('enrollment', 'No active school year is configured.');
            }

            $gradeLevel = (string) ($data['grade_level'] ?? '');
            $isSeniorHigh = in_array($gradeLevel, ['11', '12'], true);

            if ($isSeniorHigh) {
                if (empty($data['semester'])) {
                    $validator->errors()->add('semester', 'Semester is required for Grade 11 or 12.');
                }

                if (empty($data['cluster_ID'])) {
                    $validator->errors()->add('cluster_ID', 'Cluster is required for Grade 11 or 12.');
                }

                if (empty($data['course_ID'])) {
                    $validator->errors()->add('course_ID', 'Preferred course is required for Grade 11 or 12.');
                }

                if (! empty($data['course_ID']) && ! empty($data['cluster_ID'])) {
                    $courseBelongsToCluster = PreferredCourse::query()
                        ->where('course_ID', $data['course_ID'])
                        ->where('cluster_ID', $data['cluster_ID'])
                        ->exists();

                    if (! $courseBelongsToCluster) {
                        $validator->errors()->add('course_ID', 'Preferred course must belong to the selected cluster.');
                    }
                }
            }

            if (LearnerType::requiresPreviousSchool($data['learner_type'] ?? null)) {
                foreach (['last_grade_level_completed', 'last_school_year_completed', 'school_id_from_previous_school'] as $field) {
                    if (empty($data[$field])) {
                        $validator->errors()->add($field, 'This field is required for transferees or returning learners.');
                    }
                }

                if ($academicYear) {
                    foreach (self::previousSchoolValidationErrors($data, $academicYear) as $field => $message) {
                        $validator->errors()->add($field, $message);
                    }
                }
            }

            if (($data['ip_community'] ?? null) === 'Yes' && empty($data['ip_details'])) {
                $validator->errors()->add('ip_details', 'Please specify the IP/Community.');
            }

            if (($data['pwd'] ?? null) === 'Yes' && empty($data['pwd_details'])) {
                $validator->errors()->add('pwd_details', 'Please specify the disability.');
            }
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function normalized(array $validated): array
    {
        $validated['curr_country'] = 'Philippines';
        $validated['perm_country'] = 'Philippines';

        if (! empty($validated['same_address'])) {
            $validated['perm_house_no'] = $validated['curr_house_no'] ?? null;
            $validated['perm_street_name'] = $validated['curr_street_name'] ?? null;
            $validated['perm_barangay'] = $validated['curr_barangay'] ?? null;
            $validated['perm_municipality_city'] = $validated['curr_municipality_city'] ?? null;
            $validated['perm_province'] = $validated['curr_province'] ?? null;
            $validated['perm_country'] = $validated['curr_country'] ?? null;
            $validated['perm_zip_code'] = $validated['curr_zip_code'] ?? null;
        }

        if (! LearnerType::requiresPreviousSchool($validated['learner_type'] ?? null)) {
            $validated['last_grade_level_completed'] = null;
            $validated['last_school_year_completed'] = null;
            $validated['school_id_from_previous_school'] = null;
        }

        $isSeniorHigh = in_array((string) ($validated['grade_level'] ?? ''), ['11', '12'], true);

        if (! $isSeniorHigh) {
            $validated['semester'] = null;
            $validated['cluster_ID'] = null;
            $validated['course_ID'] = null;
        }

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(?Enrollment $enrollment): array
    {
        if (! $enrollment) {
            return [];
        }

        $enrollment->loadMissing([
            'student.profile',
            'student.guardians',
            'student.addresses',
        ]);

        $student = $enrollment->student;
        $profile = $student?->profile;
        $current = $student?->addresses?->firstWhere('address_type', 'current');
        $permanent = $student?->addresses?->firstWhere('address_type', 'permanent');
        $father = $student?->guardians?->firstWhere('relationship', 'father');
        $mother = $student?->guardians?->firstWhere('relationship', 'mother');
        $guardian = $student?->guardians?->firstWhere('relationship', 'guardian');
        $sameAddress = $current && $permanent
            && (string) $current->house_no === (string) $permanent->house_no
            && (string) $current->street_name === (string) $permanent->street_name
            && (string) $current->barangay === (string) $permanent->barangay
            && (string) $current->municipality === (string) $permanent->municipality
            && (string) $current->province === (string) $permanent->province
            && (string) $current->zip_code === (string) $permanent->zip_code;
        $lastCompleted = (string) ($enrollment->last_grade_level_completed ?? '');

        if (str_starts_with($lastCompleted, 'grade_')) {
            $lastCompleted = str_replace('grade_', '', $lastCompleted);
        }

        return [
            'grade_level' => preg_replace('/\D+/', '', (string) $enrollment->grade_level) ?: '',
            'LRN' => $student?->lrn,
            'semester' => $enrollment->semester,
            'cluster_ID' => $enrollment->cluster_ID,
            'course_ID' => $enrollment->course_ID,
            'learner_type' => $enrollment->learner_type,
            'last_grade_level_completed' => $lastCompleted,
            'last_school_year_completed' => $enrollment->last_school_year_completed,
            'school_id_from_previous_school' => $enrollment->school_id_from_previous_school,
            'last_school_attended' => $enrollment->last_school_attended,
            'first_name' => $student?->first_name,
            'middle_name' => $student?->middle_name,
            'last_name' => $student?->last_name,
            'suffix' => $student?->suffix,
            'birthdate' => $student?->birthdate?->format('Y-m-d'),
            'birthplace' => $student?->birthplace,
            'gender' => filled($student?->sex) ? ucfirst((string) $student->sex) : '',
            'contact_no' => $student?->contact_no,
            'email' => $student?->email,
            'religion' => $student?->religion,
            'mother_tongue' => $student?->mother_tongue,
            'ip_community' => $profile?->is_ip ? 'Yes' : 'No',
            'ip_details' => $profile?->ip_community,
            'four_ps_beneficiary' => $profile?->is_4ps ? 'Yes' : 'No',
            'four_ps_details' => $profile?->four_ps_household_id,
            'pwd' => $profile?->has_disability ? 'Yes' : 'No',
            'pwd_details' => $profile?->disability_name,
            'curr_house_no' => $current?->house_no,
            'curr_street_name' => $current?->street_name,
            'curr_barangay' => $current?->barangay,
            'curr_municipality_city' => $current?->municipality,
            'curr_province' => $current?->province,
            'curr_country' => $current?->country ?: 'Philippines',
            'curr_zip_code' => $current?->zip_code,
            'perm_house_no' => $permanent?->house_no,
            'perm_street_name' => $permanent?->street_name,
            'perm_barangay' => $permanent?->barangay,
            'perm_municipality_city' => $permanent?->municipality,
            'perm_province' => $permanent?->province,
            'perm_country' => $permanent?->country ?: 'Philippines',
            'perm_zip_code' => $permanent?->zip_code,
            'same_address' => $sameAddress ? '1' : '',
            'father_lname' => $father?->last_name,
            'father_fname' => $father?->first_name,
            'father_mname' => $father?->middle_name,
            'father_suffix' => $father?->suffix,
            'father_contact_no' => $father?->is_deceased ? null : $father?->contact_no,
            'father_is_deceased' => (bool) $father?->is_deceased,
            'mother_lname' => $mother?->last_name,
            'mother_fname' => $mother?->first_name,
            'mother_mname' => $mother?->middle_name,
            'mother_suffix' => $mother?->suffix,
            'mother_contact_no' => $mother?->is_deceased ? null : $mother?->contact_no,
            'mother_is_deceased' => (bool) $mother?->is_deceased,
            'guardian_lname' => $guardian?->last_name,
            'guardian_fname' => $guardian?->first_name,
            'guardian_mname' => $guardian?->middle_name,
            'guardian_suffix' => $guardian?->suffix,
            'guardian_contact_no' => $guardian?->is_deceased ? null : $guardian?->contact_no,
            'guardian_is_deceased' => (bool) $guardian?->is_deceased,
        ];
    }

    /**
     * @return array{available: bool, message: ?string}
     */
    public static function lrnAvailability(string $lrn, ?int $ignoreStudentId = null): array
    {
        $exists = Student::query()
            ->where('lrn', $lrn)
            ->when($ignoreStudentId, fn ($query) => $query->whereKeyNot($ignoreStudentId))
            ->exists();

        return [
            'available' => ! $exists,
            'message' => $exists ? 'This LRN is already registered.' : null,
        ];
    }

    /**
     * @return array{available: bool, message: ?string}
     */
    public static function emailAvailability(string $email, ?int $ignoreStudentId = null): array
    {
        $exists = Student::query()
            ->where('email', $email)
            ->when($ignoreStudentId, fn ($query) => $query->whereKeyNot($ignoreStudentId))
            ->exists();

        return [
            'available' => ! $exists,
            'message' => $exists ? 'This email is already registered.' : null,
        ];
    }

    private static function mergeLastSchoolYearCompleted(Request $request): void
    {
        if (! LearnerType::requiresPreviousSchool($request->input('learner_type'))) {
            $request->merge([
                'last_grade_level_completed' => null,
                'last_school_year_completed' => null,
                'last_school_year_completed_start' => null,
                'last_school_year_completed_end' => null,
                'school_id_from_previous_school' => null,
            ]);

            return;
        }

        if (filled($request->input('last_school_year_completed'))) {
            return;
        }

        $start = trim((string) $request->input('last_school_year_completed_start', ''));
        $end = trim((string) $request->input('last_school_year_completed_end', ''));

        if ($start !== '' || $end !== '') {
            $request->merge([
                'last_school_year_completed' => "{$start}-{$end}",
            ]);
        }

    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, string>
     */
    public static function previousSchoolValidationErrors(array $validated, AcademicYear $academicYear): array
    {
        $errors = [];

        if ((int) ($validated['last_grade_level_completed'] ?? 0) >= (int) ($validated['grade_level'] ?? 0)) {
            $errors['last_grade_level_completed'] = 'Last grade level completed must be lower than the grade level you wish to enroll in.';
        }

        if ((int) ($validated['last_grade_level_completed'] ?? 0) === 6 && (int) ($validated['grade_level'] ?? 0) !== 7) {
            $errors['last_grade_level_completed'] = 'Grade 6 can only be selected as last grade completed when enrolling in Grade 7.';
        }

        if (preg_match('/^(\d{4})-(\d{4})$/', (string) ($validated['last_school_year_completed'] ?? ''), $matches)) {
            $startYear = (int) $matches[1];
            $endYear = (int) $matches[2];

            if ($endYear !== $startYear + 1) {
                $errors['last_school_year_completed'] = 'School year completed must be one consecutive year, like 2025-2026.';
            }

            if ($startYear < 2000) {
                $errors['last_school_year_completed'] = 'School year completed must start from 2000 or later.';
            }

            if ($startYear > now()->year) {
                $errors['last_school_year_completed'] = 'School year completed cannot start after the current year.';
            }

            if (preg_match('/^(\d{4})-(\d{4})$/', (string) $academicYear->school_year, $activeYearMatches)
                && $endYear > (int) $activeYearMatches[2]) {
                $errors['last_school_year_completed'] = 'School year completed cannot be later than the current school year.';
            }
        }

        return $errors;
    }
}
