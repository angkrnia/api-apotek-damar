<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductExport implements FromCollection, WithMapping, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Product::with(['baseUnit', 'units'])->get();
    }

    public function map($product): array
    {
        return [
            $product->name,
            $product->sku,
            $product->baseUnit->name,
            $product->base_stock,
            'Rp ' . number_format($product->purchase_price, 0, ',', '.'),
            'Rp ' . number_format($product->units->first()?->pivot?->new_price ?? 0, 0, ',', '.'),
            $product->is_active ? 'Aktif' : 'Tidak Aktif',
        ];
    }

    public function headings(): array
    {
        return [
            'Nama Produk',
            'SKU',
            'Satuan Dasar',
            'Stok Dasar',
            'Harga Beli',
            'Harga Jual',
            'Status',
        ];
    }
}
