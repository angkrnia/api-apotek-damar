<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductUnits extends Pivot
{
    use HasFactory;

    protected $table = 'product_units';

    protected $fillable = [
        'product_id',
        'unit_id',
        'conversion_to_base',
        'is_base',
        'description',
        'stock',
        'sell_price',
        'new_price',
    ];

    protected $casts = [
        'is_base' => 'boolean',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            $item->created_by = auth()->check() ? auth()->user()->fullname : 'system';
        });

        static::updating(function ($item) {
            $item->updated_by = auth()->check() ? auth()->user()->fullname : 'system';
        });
    }
}
