<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Job extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'company',
        'title',
        'description',
        'location',
        'salary',
        'posted_by',
    ];

    protected function casts(): array
    {
        return [
            'salary' => 'decimal:2',
        ];
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
