<?php

namespace App\Models\Opname;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpnameDetail extends Model
{
    use HasFactory;

    protected $table = 'stock_opname_product_line';
    protected $fillable = [
        'opname_header_id',
        'product_id',
        'qty_system',
        'qty_real',
        'qty_diff',
        'note',
        'created_by',
        'updated_by',
    ];
    protected $appends = ['has_transaction', 'diff_transaction'];

    public function getHasTransactionAttribute()
    {
        return $this->qty_system !== optional($this->product)->base_stock;
    }

    public function getDiffTransactionAttribute()
    {
        return optional($this->product)->base_stock - $this->qty_system;
    }

    public function scopeKeywordSearch(Builder $query, string $searchKeyword): Builder
    {
        $searchable = ['qty_system', 'qty_real', 'qty_diff', 'note'];
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
        // return $query->where(function ($query) use ($searchKeyword) {
        //     // Cari di kolom-kolom tabel Queue
        //     foreach ($this->getFillable() as $column) {
        //         $query->orWhere($this->getTable() . '.' . $column, 'LIKE', "%$searchKeyword%");
        //     }
        // });
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id')->select('id', 'name', 'sku', 'base_stock', 'base_unit_id');
    }
}
