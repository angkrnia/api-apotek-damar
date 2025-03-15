<?php

namespace App\Models\Opname;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpnameHeader extends Model
{
    use HasFactory;

    protected $table = 'stock_opname';

    protected $primaryKey = 'id';
    protected $fillable = [
        'code',
        'date',
        'pic',
        'note',
        'status',
        'created_by',
        'updated_by',
    ];

    public function scopeKeywordSearch(Builder $query, string $searchKeyword): Builder
    {
        $searchable = ['code', 'pic', 'note'];
        return $query->where(function ($query) use ($searchKeyword, $searchable) {
            foreach ($searchable as $column) {
                $query->orWhere($this->getTable() . '.' . $column, 'LIKE', "%$searchKeyword%");
            }
        });
    }

    public function productsLines()
    {
        return $this->hasMany(OpnameDetail::class);
    }
}
