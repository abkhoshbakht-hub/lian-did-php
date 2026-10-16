<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Article extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = 'c' . Str::random(24);
            }
        });
    }

    protected $fillable = [
        'id', 'title', 'slug', 'subtitle', 'content', 'excerpt', 
        'featuredImage', 'status', 'viewCount', 'isFeatured', 
        'isBreaking', 'isPinned', 'placement', 'source', 'sourceUrl', 
        'publishedAt', 'scheduledAt', 'authorId', 'categoryId', 
        'metaTitle', 'metaDesc', 'metaKeywords', 'region'
    ];

    protected function casts(): array
    {
        return [
            'publishedAt' => 'datetime',
            'scheduledAt' => 'datetime',
            'isFeatured' => 'boolean',
            'isBreaking' => 'boolean',
            'isPinned' => 'boolean',
        ];
    }

    // ارتباط با نویسنده
    public function author()
    {
        return $this->belongsTo(User::class, 'authorId');
    }

    // ارتباط با دسته‌بندی
    public function category()
    {
        return $this->belongsTo(Category::class, 'categoryId');
    }

    // ارتباط با برچسب‌ها
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'article_tag', 'article_id', 'tag_id');
    }

    // ارتباط با نظرات
    public function comments()
    {
        return $this->hasMany(Comment::class, 'articleId');
    }
}