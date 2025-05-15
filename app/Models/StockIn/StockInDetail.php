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
        'last_buy_price',
        'last_sell_price',
        'created_by',
        'updated_by',
    ];
    protected $casts = [
        'quantity' => 'integer',
        'buy_price' => 'decimal:2',
    ];
    protected $appends = ['buy_price_diff', 'buy_price_direction'];

    public function getBuyPriceDiffAttribute()
    {
        $productPrice = optional($this->product)->purchase_price;

        if (is_null($productPrice)) {
            return false;
        }

        return bccomp($this->buy_price, $productPrice, 2) !== 0;
    }

    public function getBuyPriceDirectionAttribute()
    {
        $productPrice = optional($this->product)->purchase_price;

        if (is_null($productPrice)) {
            return null;
        }

        return match (bccomp($this->buy_price, $productPrice, 2)) {
            1 => 'UP',    // buy_price lebih tinggi dari purchase_price
            -1 => 'DOWN',  // buy_price lebih rendah dari purchase_price
            0 => null,   // sama
            default => null,
        };
    }

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
        return $this->belongsTo(Product::class, 'product_id')->select('id', 'name', 'sku', 'base_stock', 'purchase_price');
    }

    public function productUnit()
    {
        return $this->belongsTo(ProductUnits::class, 'product_unit_id')->select('id', 'unit_id', 'product_id', 'sell_price', 'new_price');
    }
}
