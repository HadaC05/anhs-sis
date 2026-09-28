<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SchoolInformation extends Model
{
    protected $table = 'school_information';

    protected $fillable = ['name', 'school_id', 'region', 'division', 'district', 'logo_path'];

    public static function current(): self
    {
        return static::query()->find(1) ?? new static([
            'name' => config('app.name'),
            'region' => 'Region XIII',
        ]);
    }

    public function logoDataUri(): ?string
    {
        if (! $this->logo_path || ! Storage::disk('public')->exists($this->logo_path)) {
            return null;
        }

        return 'data:'.Storage::disk('public')->mimeType($this->logo_path).';base64,'.base64_encode(Storage::disk('public')->get($this->logo_path));
    }
}
