<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'created_by',
        'updated_by',
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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($unit) {
            $unit->created_by = auth()->check() ? auth()->user()->fullname : 'system';
        });

        static::updating(function ($unit) {
            $unit->updated_by = auth()->check() ? auth()->user()->fullname : 'system';
        });
    }
}
