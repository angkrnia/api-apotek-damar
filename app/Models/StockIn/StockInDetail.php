<?php

namespace App\Models\StockIn;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockInDetail extends Model
{
    use HasFactory;
    protected $table = 'stock_in_product_line';
    protected $fillable = [
        'stock_in_id',
        'product_id',
        'quantity',
        'buy_price',
        'note',
        'created_by',
        'updated_by',
    ];
    protected $casts = [
        'quantity' => 'integer',
        'buy_price' => 'decimal:2',
    ];

    public function scopeKeywordSearch(Builder $query, string $searchKeyword): Builder
    {
        $searchable = ['quantity', 'buy_price', 'note'];
        return $query->where(function ($query) use ($searchKeyword, $searchable) {
            foreach ($searchable as $column) {
                $query->orWhere($this->getTable() . '.' . $column, 'LIKE', "%$searchKeyword%");
            }
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id')->select('id', 'name', 'sku', 'base_stock');
    }
}
