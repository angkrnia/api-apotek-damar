<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'sku',
        'barcode',
        'category_id',
        'group_id',
        'base_unit_id',
        'base_stock',
        'image',
        'type',
        'side_effect',
        'rack_location',
        'description',
        'dosage',
        'purchase_price',
        'indication',
        'is_need_receipt',
        'is_active',
        'created_by',
        'updated_by',
    ];
    protected $casts = [
        'purchase_price' => 'float',
        'is_need_receipt' => 'boolean',
        'is_active' => 'boolean',
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

    // set slug
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        $slug = Str::slug($value);
        $originalSlug = $slug;
        $counter = 1;

        // Cek apakah slug sudah ada di database menggunakan Eloquent
        while (static::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        $this->attributes['slug'] = $slug;
    }

    // relasi ke group
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    // relasi ke category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // relasi ke units
    public function units()
    {
        return $this->belongsToMany(Unit::class, 'product_units', 'product_id', 'unit_id')
            ->withPivot('conversion_to_base', 'is_base', 'description', 'sell_price', 'new_price')
            ->orderBy('product_units.created_at', 'asc');
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
