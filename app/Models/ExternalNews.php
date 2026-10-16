<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ExternalNews extends Model
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
        'id', 'title', 'link', 'description', 'image', 'source', 
        'sourceName', 'category', 'status', 'isBreaking', 'region', 
        'topic', 'publishedAt', 'fetchedAt'
    ];

    protected function casts(): array
    {
        return [
            'isBreaking' => 'boolean',
            'publishedAt' => 'datetime',
            'fetchedAt' => 'datetime',
        ];
    }

    // ارتباط با باکس‌های صفحه اصلی (یک خبر ممکن است در چند باکس نمایش داده شود)
    public function homepageSlots()
    {
        return $this->hasMany(HomepageSlot::class, 'externalNewsId');
    }
}