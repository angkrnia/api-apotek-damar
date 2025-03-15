<?php

namespace App\Models\Sale;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleHeader extends Model
{
    use HasFactory;

    protected $table = 'sales';

    protected $primaryKey = 'id';
    protected $fillable = [
        'receipt_number',
        'patient_name',
        'payment_method',
        'status',
        'grand_total',
        'paid_amount',
        'change',
        'note',
        'created_by',
        'cashier_id',
        'updated_by',
    ];
    protected $casts = [
        'grand_total' => 'float',
        'paid_amount' => 'float',
        'change' => 'float',
    ];

    // Event untuk generate nomor transaksi sebelum insert ke database
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sale) {
            $sale->receipt_number = self::generateReceiptNumber();
        });
    }

    // Fungsi untuk generate receipt number
    private static function generateReceiptNumber()
    {
        $year = date('Y');  // Tahun sekarang (2025)
        $month = str_pad(date('m'), 2, '0', STR_PAD_LEFT); // Bulan sekarang (02)

        // Ambil nomor terakhir dari database
        $lastSale = self::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->latest('id')
            ->first();

        // Nomor urut terakhir, jika belum ada maka mulai dari 1
        $lastNumber = $lastSale ? (int) substr($lastSale->receipt_number, -4) : 0;
        $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

        return "TRX{$year}{$month}{$newNumber}";
    }

    // search data
    public function scopeKeywordSearch(Builder $query, string $searchKeyword): Builder
    {
        $columns = ['receipt_number', 'patient_name', 'note', 'created_by'];
        return $query->where(function ($query) use ($searchKeyword, $columns) {
            foreach ($columns as $column) {
                $query->orWhere($column, 'LIKE', "%$searchKeyword%");
            }
        });
    }
}
