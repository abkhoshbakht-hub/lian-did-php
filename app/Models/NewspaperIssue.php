<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewspaperIssue extends Model
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
        'id', 'newspaperId', 'date', 'persianDate', 'issueNumber', 
        'title', 'originalUrl', 'imageUrl', 'thumbnailUrl', 
        'sourceId', 'imageHash', 'confidence', 'status', 'publishedAt'
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'publishedAt' => 'datetime',
            'confidence' => 'integer',
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