<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewspaperSource extends Model
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
        'id', 'newspaperId', 'name', 'type', 'url', 'priority', 
        'active', 'parserType', 'configuration', 'lastSuccessAt', 
        'lastFailureAt', 'lastError'
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'priority' => 'integer',
            'configuration' => 'array', // تبدیل JSON به آرایه
            'lastSuccessAt' => 'datetime',
            'lastFailureAt' => 'datetime',
        ];
    }

    public function newspaper()
    {
        return $this->belongsTo(Newspaper::class, 'newspaperId');
    }

    public function issues()
    {
        return $this->hasMany(NewspaperIssue::class, 'sourceId');
    }

    public function fetchLogs()
    {
        return $this->hasMany(NewspaperFetchLog::class, 'sourceId');
    }
}