<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewspaperFetchLog extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;
    
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
        'id', 'newspaperId', 'sourceId', 'startedAt', 'finishedAt', 
        'status', 'httpStatus', 'errorMessage', 'discoveredImageUrl', 
        'discoveredDate', 'responseTimeMs', 'createdAt'
    ];

    protected function casts(): array
    {
        return [
            'startedAt' => 'datetime',
            'finishedAt' => 'datetime',
            'createdAt' => 'datetime',
            'httpStatus' => 'integer',
            'responseTimeMs' => 'integer',
        ];
    }

    public function newspaper()
    {
        return $this->belongsTo(Newspaper::class, 'newspaperId');
    }

    public function source()
    {
        return $this->belongsTo(NewspaperSource::class, 'sourceId');
    }
}