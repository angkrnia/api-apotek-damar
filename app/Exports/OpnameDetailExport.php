<?php

namespace App\Exports;

use App\Models\Opname\OpnameDetail;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OpnameDetailExport implements FromCollection, WithMapping, WithHeadings
{
    protected $opnameId;

    public function __construct($opnameId)
    {
        $this->opnameId = $opnameId;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return OpnameDetail::with(['product', 'product.baseUnit'])->where('opname_header_id', $this->opnameId)->get();
    }

    public function map($detail): array
    {
        return [
            $detail->product?->name ?? '-',
            $detail->product?->sku ?? '-',
            $detail->product?->baseUnit?->name ?? '-',
            $detail->qty_system ?? 0,
            $detail->qty_real ?? 0,
            $detail->qty_diff ?? 0,
            $detail->note ?? '-',
        ];
    }

    public function headings(): array
    {
        return [
            'Nama Produk',
            'SKU',
            'Satuan Dasar',
            'QTY Sistem',
            'QTY Real',
            'QTY Penyesuaian',
            'Catatan',
        ];
    }
}
