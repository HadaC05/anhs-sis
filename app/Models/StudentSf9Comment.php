<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentSf9Comment extends Model
{
    protected $fillable = ['enrollment_ID', 'grading_period', 'comment', 'posted_by'];
}
