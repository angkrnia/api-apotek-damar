<?php

namespace App\Models\StockIn;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockInHeader extends Model
{
    use HasFactory;
    protected $table = 'stock_in';

    protected $primaryKey = 'id';
    protected $fillable = [
        'date_in',
        'source',
        'note',
        'status',
        'created_by',
        'updated_by',
    ];
    protected $appends = [
        'total_products',
    ];

    public function getTotalProductsAttribute()
    {
        return $this->productsLines()->count();
    }

    public function scopeKeywordSearch(Builder $query, string $searchKeyword): Builder
    {
        $searchable = ['source', 'note'];
        return $query->where(function ($query) use ($searchKeyword, $searchable) {
            foreach ($searchable as $column) {
                $query->orWhere($this->getTable() . '.' . $column, 'LIKE', "%$searchKeyword%");
            }
        });
    }

    public function productsLines()
    {
        return $this->hasMany(StockInDetail::class, 'stock_in_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            $item->created_by = auth()->check() ? auth()->user()->fullname : 'system';
            $item->date_in = now();
        });

        static::updating(function ($item) {
            $item->updated_by = auth()->check() ? auth()->user()->fullname : 'system';
        });
    }
}
