<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'headline',
        'phone',
        'bio',
        'skills',
        'avatar',
        'cover',
        'location',
        'graduate_year',
        'current_job',
        'company',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getAvatarAttribute($value)
    {
        return $this->toPublicUrl($value);
    }

    public function getCoverAttribute($value)
    {
        return $this->toPublicUrl($value);
    }

    public function setAvatarAttribute($value)
    {
        $this->attributes['avatar'] = $this->normalizeStoredPath($value);
    }

    public function setCoverAttribute($value)
    {
        $this->attributes['cover'] = $this->normalizeStoredPath($value);
    }

    private function toPublicUrl($value)
    {
        if (!$value) {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://', '/storage/'])) {
            return $value;
        }

        return Storage::disk('public')->url($value);
    }

    private function normalizeStoredPath($value)
    {
        if (!$value || !is_string($value)) {
            return $value;
        }

        if (Str::startsWith($value, '/storage/')) {
            return Str::replaceFirst('/storage/', '', $value);
        }

        return $value;
    }
}
