<?php

namespace App\Models\Sale;

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
        'quantity',
        'subtotal',
        'note',
        'created_by',
        'updated_by',
    ];
    protected $casts = [
        'product_unit_price' => 'float',
        'subtotal' => 'float',
    ];
}
