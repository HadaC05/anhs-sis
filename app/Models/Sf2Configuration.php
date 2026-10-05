<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sf2Configuration extends Model
{
    protected $fillable = ['format'];

    public static function formats(): array
    {
        return [
            'legacy' => ['label' => 'Old SF2 form', 'description' => 'Absent and Tardy monthly totals. Upload a text-based PDF or Excel (.xls or .xlsx).', 'accept' => 'application/pdf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,.pdf,.xls,.xlsx'],
            'lis' => ['label' => 'New SF2 form', 'description' => 'Absent and Present monthly totals. Upload a completed LIS PDF or Excel (.xls or .xlsx).', 'accept' => 'application/pdf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,.pdf,.xls,.xlsx'],
        ];
    }

    public static function current(): self
    {
        return self::query()->find(1) ?? new self(['format' => 'legacy']);
    }

    public function selectedFormat(): array
    {
        return self::formats()[$this->format];
    }
}
