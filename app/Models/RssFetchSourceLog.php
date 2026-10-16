<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RssFetchSourceLog extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // این جدول کلاً timestamp پیش‌فرض ندارد

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
        'id', 'fetchLogId', 'source', 'status', 'startedAt', 
        'finishedAt', 'durationMs', 'fetchedCount', 'newCount', 
        'error', 'retryCount'
    ];

    protected function casts(): array
    {
        return [
            'startedAt' => 'datetime',
            'finishedAt' => 'datetime',
        ];
    }

    public function fetchLog()
    {
        return $this->belongsTo(RssFetchLog::class, 'fetchLogId');
    }
}