<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Subscriber extends Model
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

    protected $fillable = ['id', 'email', 'name', 'isActive'];

    protected function casts(): array
    {
        return [
            'isActive' => 'boolean',
        ];
    }
}