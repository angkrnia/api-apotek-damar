<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'movement_type',
        'qty_in',
        'qty_out',
        'remaining',
        'reference_type',
        'reference_id',
        'note',
        'created_by'
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

    public function product()
    {
        return $this->belongsTo(Product::class)->select('id', 'name', 'slug', 'sku', 'image');
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
