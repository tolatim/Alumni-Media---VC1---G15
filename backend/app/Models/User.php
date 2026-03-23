<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'role_id',
        'email',
        'password',
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
        'suspended_until',
        'suspended_permanently',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'suspended_until' => 'datetime',
        'suspended_permanently' => 'boolean',
    ];

    protected $appends = [
        'name',
        'profile',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function sentConnections()
    {
        return $this->hasMany(Connection::class, 'requester_id');
    }

    public function receivedConnections()
    {
        return $this->hasMany(Connection::class, 'addressee_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function reviewedReports()
    {
        return $this->hasMany(Report::class, 'reviewed_by');
    }

    public function getNameAttribute(): string
    {
        $full = trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
        if ($full !== '') {
            return $full;
        }

        return (string) ($this->attributes['name'] ?? '');
    }

    public function isSuspended(): bool
    {
        if ((bool) $this->suspended_permanently) {
            return true;
        }

        if (!$this->suspended_until) {
            return false;
        }

        return $this->suspended_until->isFuture();
    }

    public function getProfileAttribute(): array
    {
        $avatar = $this->avatar;
        if ($avatar && !str_starts_with($avatar, 'http://') && !str_starts_with($avatar, 'https://')) {
            if (!str_starts_with($avatar, '/storage/')) {
                $avatar = Storage::disk('public')->url($avatar);
            }
        }

        $cover = $this->cover;
        if ($cover && !str_starts_with($cover, 'http://') && !str_starts_with($cover, 'https://')) {
            if (!str_starts_with($cover, '/storage/')) {
                $cover = Storage::disk('public')->url($cover);
            }
        }

        return [
            'headline' => $this->headline,
            'phone' => $this->phone,
            'bio' => $this->bio,
            'skills' => $this->skills,
            'avatar' => $avatar,
            'cover' => $cover,
            'location' => $this->location,
            'graduate_year' => $this->graduate_year,
            'current_job' => $this->current_job,
            'company' => $this->company,
        ];
    }
}
