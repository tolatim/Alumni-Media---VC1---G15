<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'file_path',
        'type',
    ];

    protected $appends = ['media_url'];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function getMediaUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        if (str_starts_with($this->file_path, 'http://') || str_starts_with($this->file_path, 'https://')) {
            return $this->file_path;
        }

        if (str_starts_with($this->file_path, '/storage/')) {
            return $this->file_path;
        }

        return Storage::disk('public')->url($this->file_path);
    }
}