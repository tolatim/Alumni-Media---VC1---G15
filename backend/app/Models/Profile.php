<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'headline',
        'phone',
        'bio',
        'avatar',
        'location',
        'graduate_year',
        'current_job',
        'company',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
