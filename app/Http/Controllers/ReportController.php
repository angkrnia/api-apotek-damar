<?php

namespace App\Http\Controllers;

use App\Exports\OpnameDetailExport;
use App\Exports\ProductExport;
use App\Exports\StockInDetailExport;
use App\Models\Opname\OpnameHeader;
use App\Models\StockIn\StockInHeader;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    // All Products
    public function allProducts()
    {
        $filename = 'all-products-' . date('d-m-Y') . '.xlsx';

        return Excel::download(new ProductExport, $filename);
    }

    // Download excel stock opname detail
    public function stockOpnameDetail(Request $request, OpnameHeader $opname)
    {
        $filename = 'stock-opname-' . $opname->created_at->format('d-m-Y H:i:s') . '.xlsx';

        return Excel::download(new OpnameDetailExport($opname->id), $filename);
    }

    // Download excel stock entry detail
    public function stockEntryDetail(Request $request, StockInHeader $stockIn)
    {
        $filename = 'stock-entry-' . $stockIn->created_at->format('d-m-Y H:i:s') . '.xlsx';

        return Excel::download(new StockInDetailExport($stockIn->id), $filename);
    }
}
