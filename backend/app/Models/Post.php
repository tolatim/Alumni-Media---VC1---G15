<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'content',
        'shared_post_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function media()
    {
        return $this->hasMany(Media::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function sharedPost()
    {
        return $this->belongsTo(Post::class, 'shared_post_id');
    }

    public function shares()
    {
        return $this->hasMany(Post::class, 'shared_post_id');
    }

    public function isShare(): bool
    {
        return !empty($this->shared_post_id);
    }

    public function originalPostId(): int
    {
        return (int) ($this->shared_post_id ?: $this->id);
    }

    public function scopeWithCardData(Builder $query, ?User $viewer = null, bool $includeSharedPost = true): Builder
    {
        $relations = ['user.role'];

        if (Schema::hasTable('media')) {
            $relations[] = 'media';
        }

        if ($includeSharedPost && Schema::hasColumn('posts', 'shared_post_id')) {
            $relations['sharedPost'] = function ($sharedPostQuery) use ($viewer) {
                $sharedPostQuery->withCardData($viewer, false);
            };
        }

        $query->with($relations);

        $countableRelations = [];

        if (Schema::hasTable('likes')) {
            $countableRelations[] = 'likes';
        }

        if (Schema::hasTable('comments')) {
            $countableRelations[] = 'comments';
        }

        if (Schema::hasColumn('posts', 'shared_post_id')) {
            $countableRelations[] = 'shares';
        }

        if (!empty($countableRelations)) {
            $query->withCount($countableRelations);
        }

        if ($viewer && Schema::hasTable('likes')) {
            $query->withExists([
                'likes as liked_by_me' => function ($likeQuery) use ($viewer) {
                    $likeQuery->where('user_id', $viewer->id);
                },
            ]);
        }

        return $query;
    }
}
