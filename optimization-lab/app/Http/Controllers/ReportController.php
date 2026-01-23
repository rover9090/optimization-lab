<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ReportController extends Controller
{
    public function getRegionalSales()
    {
        // This is where we bring the terminal SQL into the codebase
        $results = DB::table('orders as o')
            ->join('middleware_db.website_config as wc', 'o.locale', '=', 'wc.locale')
            ->join('order_lines as ol', 'o.id', '=', 'ol.order_id')
            ->select([
                'wc.country_short',
                DB::raw('COUNT(DISTINCT o.id) as order_count'),
                DB::raw('SUM(ol.qty) as total_qty')
            ])
            ->where('wc.country_short', 'ca')
            ->groupBy('wc.country_short')
            ->get();

        return $results;
    }
}
