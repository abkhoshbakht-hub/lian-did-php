<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RssFetchLog extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;
    
    // چون در این جدول فقط createdAt داریم
    const UPDATED_AT = null;
    const CREATED_AT = 'createdAt';

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
        'id', 'startedAt', 'finishedAt', 'durationMs', 'status', 
        'totalSources', 'successSources', 'failedSources', 
        'newItems', 'updatedItems', 'errorSummary', 'triggerType', 'createdAt'
    ];

    protected function casts(): array
    {
        return [
            'startedAt' => 'datetime',
            'finishedAt' => 'datetime',
            'createdAt' => 'datetime',
        ];
    }

    public function sources()
    {
        return $this->hasMany(RssFetchSourceLog::class, 'fetchLogId');
    }
}