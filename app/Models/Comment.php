<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Comment extends Model
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

    protected $fillable = ['id', 'content', 'approved', 'userId', 'articleId'];

    protected function casts(): array
    {
        return [
            'approved' => 'boolean',
        ];
    }

    // ارتباط با نویسنده نظر
    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    // ارتباط با مقاله‌ای که نظر برای آن ثبت شده
    public function article()
    {
        return $this->belongsTo(Article::class, 'articleId');
    }
}