<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MapehComponent extends Model
{
    public $timestamps = false;

    protected $fillable = ['mapeh_configuration_id', 'curr_subj_ID', 'key'];

    public function curriculumSubject(): BelongsTo
    {
        return $this->belongsTo(CurriculumSubject::class, 'curr_subj_ID', 'curr_subj_ID');
    }
}
