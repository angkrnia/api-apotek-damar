<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'user_agent',
        'ip_address',
        'product_id',
        'product_unit_id',
        'quantity',
        'unit_price',
        'subtotal',
        'status',
        'note',
        'created_by',
        'updated_by',
    ];
    protected $casts = [
        'unit_price' => 'float',
        'subtotal' => 'float',
        'quantity' => 'integer',
    ];

    // search data
    public function scopeKeywordSearch(Builder $query, string $searchKeyword): Builder
    {
        $searchable = ['quantity', 'unit_price', 'subtotal', 'note'];
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
        return $this->belongsTo(Product::class, 'product_id')->select('id', 'name', 'sku', 'base_stock' , 'purchase_price');
    }

    public function productUnit()
    {
        return $this->belongsTo(ProductUnits::class, 'product_unit_id')->select('id', 'unit_id', 'product_id', 'new_price');
    }
}
