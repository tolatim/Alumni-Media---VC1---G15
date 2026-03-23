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
        $visibleAuthorIds = self::visibleAuthorIdsForViewer($viewer);

        if (Schema::hasTable('media')) {
            $relations[] = 'media';
        }

        if ($includeSharedPost && Schema::hasColumn('posts', 'shared_post_id')) {
            $relations['sharedPost'] = function ($sharedPostQuery) use ($viewer, $visibleAuthorIds) {
                if (is_array($visibleAuthorIds)) {
                    $sharedPostQuery->whereIn('user_id', $visibleAuthorIds);
                }

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

    protected static function visibleAuthorIdsForViewer(?User $viewer): ?array
    {
        if (!$viewer) {
            return null;
        }

        $visibleUserIds = [(int) $viewer->id];

        if (!Schema::hasTable('connections')) {
            return $visibleUserIds;
        }

        $friendIds = Connection::query()
            ->where('status', 'accepted')
            ->where(function ($connectionQuery) use ($viewer) {
                $connectionQuery->where('requester_id', $viewer->id)
                    ->orWhere('addressee_id', $viewer->id);
            })
            ->get()
            ->map(function ($row) use ($viewer) {
                return (int) ($row->requester_id === (int) $viewer->id
                    ? $row->addressee_id
                    : $row->requester_id);
            })
            ->values()
            ->all();

        return array_values(array_unique(array_merge($visibleUserIds, $friendIds)));
    }
}
