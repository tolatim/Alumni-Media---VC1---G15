<?php

namespace App\Models;

use App\Support\WebsocketNotifier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'reportable_id',
        'reportable_type',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (Report $report): void {
            WebsocketNotifier::send('admin_activity', [
                'event' => 'report_submitted',
                'report_id' => $report->id,
                'reporter_id' => $report->user_id,
                'target_type' => class_basename((string) $report->reportable_type),
                'target_id' => $report->reportable_id,
                'reason' => $report->reason,
                'occurred_at' => optional($report->created_at)->toIso8601String() ?? now()->toIso8601String(),
            ], 'admins');
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function reportable()
    {
        return $this->morphTo();
    }
}
