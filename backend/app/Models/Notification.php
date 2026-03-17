<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

class Notification extends DatabaseNotification
{
    public $incrementing = false;   // UUID is not auto-increment
    protected $keyType = 'string';  // UUID is string

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (string) Str::uuid(); // auto-generate UUID
            }
        });
    }
}