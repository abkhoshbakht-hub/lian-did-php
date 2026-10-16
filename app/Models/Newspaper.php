<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Newspaper extends Model
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
        'id', 'name', 'slug', 'category', 'logo', 'website', 
        'telegram', 'description', 'active', 'displayOrder'
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'displayOrder' => 'integer',
        ];
    }

    public function sources()
    {
        return $this->hasMany(NewspaperSource::class, 'newspaperId');
    }

    public function issues()
    {
        return $this->hasMany(NewspaperIssue::class, 'newspaperId');
    }

    public function fetchLogs()
    {
        return $this->hasMany(NewspaperFetchLog::class, 'newspaperId');
    }
}