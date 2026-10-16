<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HomepageSlot extends Model
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
        'id', 'slotKey', 'label', 'type', 'externalNewsId', 
        'customTitle', 'customContent', 'customImage', 'customLink', 
        'category', 'isActive', 'order'
    ];

    protected function casts(): array
    {
        return [
            'isActive' => 'boolean',
            'order' => 'integer',
        ];
    }

    // ارتباط با خبری که قرار است در این باکس نمایش داده شود
    public function externalNews()
    {
        return $this->belongsTo(ExternalNews::class, 'externalNewsId');
    }
}