<?php

namespace App\Exports;

use App\Models\StockIn\StockInDetail;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockInDetailExport implements FromCollection, WithMapping, WithHeadings
{
    protected $stockInId;

    public function __construct($stockInId)
    {
        $this->stockInId = $stockInId;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return StockInDetail::with(['product', 'productUnit.unit'])->where('stock_in_id', $this->stockInId)->get();
    }

    public function map($detail): array
    {
        return [
            $detail->product?->name ?? '-',
            $detail->product?->sku ?? '-',
            convertRp($detail->last_sell_price ?? 0),
            convertRp($detail->productUnit?->new_price ?? 0),
            convertRp($detail->last_buy_price),
            convertRp($detail->product?->purchase_price ?? 0),
            $detail->quantity ?? 0,
            convertRp($detail->quantity * $detail->buy_price),
            $detail->note ?? '-',
        ];
    }

    public function headings(): array
    {
        return [
            'Nama Produk',
            'SKU',
            'Harga Jual Sebelumnya',
            'Harga Jual Sekarang',
            'Harga Beli Sebelumnya',
            'Harga Beli Sekarang',
            'QTY',
            'Total',
            'Catatan',
        ];
    }
}
