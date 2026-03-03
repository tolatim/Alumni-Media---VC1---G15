<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'headline',
        'phone',
        'bio',
        'skills',
        'avatar',
        'avatar_url',
        'location',
        'graduate_year',
        'current_job',
        'company',
        'profile_photo',
        'cover_url'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed'
    ];
    protected $appends = ['avatar_url','cover_url'];

    public function getAvatarUrlAttribute()
    {
        return $this->avatar
            ? env('APP_URL') . asset('storage/' . $this->avatar)
            : null;
    }
    public function getCoverUrlAttribute()
    {
        return $this->avatar
            ? env('APP_URL') . asset('storage/' . $this->profile_photo)
            : null;
    }
}
