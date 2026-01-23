<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReportRegionalSales extends Command
{
    // The name and signature of the console command
    protected $signature = 'report:regional-sales {country=ca : The country code to filter by}';

    // The console command description
    protected $description = 'Generate a sales report by joining data across multiple databases';

    public function handle()
    {
        $country = $this->argument('country');
        $this->info("Fetching cross-database report for: " . strtoupper($country));

        $startTime = microtime(true);

        // Executing the Cross-Database Join logic
        $results = DB::table('orders as o')
            ->join('middleware_db.website_config as wc', 'o.locale', '=', 'wc.locale')
            ->join('order_lines as ol', 'o.id', '=', 'ol.order_id')
            ->select([
                'wc.country_short',
                'wc.language_name',
                DB::raw('COUNT(DISTINCT o.id) as total_orders'),
                DB::raw('SUM(ol.qty) as total_items_sold')
            ])
            ->where('wc.country_short', $country)
            ->groupBy('wc.country_short', 'wc.language_name')
            ->get();

        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 4);

        if ($results->isEmpty()) {
            $this->error("No data found for country: $country");
            return;
        }

        // Display the data in a professional table format
        $this->table(
            ['Country', 'Language', 'Order Count', 'Items Sold'],
            $results->toArray()
        );

        $this->warn("Execution Time: {$executionTime} seconds");
    }
}
