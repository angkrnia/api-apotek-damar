<?php

namespace App\Models\StockIn;

use App\Models\Product;
use App\Models\ProductUnits;
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
        'product_unit_id',
        'quantity',
        'buy_price',
        'note',
        'status',
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
        $productSearchable = ['products.name', 'products.sku', 'products.description'];

        return $query
            ->leftJoin('products', 'products.id', '=', $this->getTable() . '.product_id')
            ->where(function ($query) use ($searchKeyword, $searchable, $productSearchable) {
                foreach ($searchable as $column) {
                    $query->orWhere($this->getTable() . '.' . $column, 'LIKE', "%$searchKeyword%");
                }
                foreach ($productSearchable as $column) {
                    $query->orWhere($column, 'LIKE', "%$searchKeyword%");
                }
            })
            ->select($this->getTable() . '.*');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id')->select('id', 'name', 'sku', 'base_stock');
    }

    public function productUnit()
    {
        return $this->belongsTo(ProductUnits::class, 'product_unit_id')->select('id', 'unit_id', 'product_id', 'sell_price', 'new_price');
    }
}
