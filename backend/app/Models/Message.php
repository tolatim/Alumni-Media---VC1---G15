<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Message extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'content',
        'media_path',
        'media_type',
        'status',
        'read_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    protected $appends = ['media_url'];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function getMediaUrlAttribute(): ?string
    {
        if (!$this->media_path) {
            return null;
        }

        if (str_starts_with($this->media_path, 'http://') || str_starts_with($this->media_path, 'https://')) {
            return $this->media_path;
        }

        if (str_starts_with($this->media_path, '/storage/')) {
            return $this->media_path;
        }

        return Storage::disk('public')->url($this->media_path);
    }
}
