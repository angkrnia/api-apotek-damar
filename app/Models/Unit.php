<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'created_by',
        'updated_by',
    ];

    // search data
    public function scopeKeywordSearch(Builder $query, string $searchKeyword): Builder
    {
        $columns = $this->fillable;
        return $query->where(function ($query) use ($searchKeyword, $columns) {
            foreach ($columns as $column) {
                $query->orWhere($column, 'LIKE', "%$searchKeyword%");
            }
        });
    }

    // relasi ke produk
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_units', 'unit_id', 'product_id')
            ->withPivot('conversion_to_base', 'is_base', 'stock', 'sell_price', 'new_price')
            ->withTimestamps();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($unit) {
            $unit->created_by = auth()->check() ? auth()->user()->fullname : 'system';
        });

        static::updating(function ($unit) {
            $unit->updated_by = auth()->check() ? auth()->user()->fullname : 'system';
        });
    }
}
