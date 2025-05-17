<?php

namespace App\Models\Sale;

use App\Models\Cart;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleDetail extends Model
{
    use HasFactory;

    protected $table = 'sales_product_line';

    protected $primaryKey = 'id';
    protected $fillable = [
        'sale_id',
        'cart_id',
        'product_name',
        'product_sku',
        'product_unit',
        'product_unit_price',
        'product_unit_cost',
        'quantity',
        'subtotal',
        'note',
        'created_by',
        'updated_by',
    ];
    protected $casts = [
        'product_unit_price' => 'float',
        'product_unit_cost' => 'float',
        'subtotal' => 'float',
    ];

    public function sale()
    {
        return $this->belongsTo(SaleHeader::class, 'sale_id');
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }
}
