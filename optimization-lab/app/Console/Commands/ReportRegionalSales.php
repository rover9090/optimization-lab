<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\WebsiteConfig;

class ReportRegionalSales extends Command
{
    // The name and signature of the console command
    protected $signature = 'report:regional-sales 
                            {country=ca : The country code to filter by}
                            {--optimized : Use the denormalized single-table query}';

    // The console command description
    protected $description = 'Generate a sales report by joining data across multiple databases';

    public function handle()
    {
        $country = $this->argument('country');
        $useOptimized = $this->option('optimized');
        $this->info("Fetching cross-database report for: " . strtoupper($country));

        $startTime = microtime(true);
        $order_table = (new Order)->getTable();

        if ($useOptimized) {
            $this->warn("MODE: Optimized (Separately scan)");
            // No Join to website_config
            $website_configs = WebsiteConfig::select(
                'country_short',
                'language_name',
                'locale'
            )->where('country_short',$country)
            ->get()->keyBy('locale');
            $locales = $website_configs->pluck('locale');
            $results = collect([]);
            
            $query_results = Order::select([
                    'o.locale',
                    DB::raw('COUNT(DISTINCT o.id) as total_orders'),
                    DB::raw('SUM(ol.qty) as total_items_sold')
                ])
                ->from($order_table.' as o')
                ->join('order_lines as ol', 'o.id', '=', 'ol.order_id')
                ->whereIn('o.locale', $locales)
                ->groupBy('o.locale')
                ->get();
            foreach($query_results as $v){
                $config = $website_configs->get($v->locale);

                if (!$config) {
                    $this->error("Missing config for locale: {$v->locale}");
                    continue;
                }

                $results->push([
                    'country_short'    => $config->country_short,
                    'language_name'    => $config->language_name,
                    'total_orders'     => $v->total_orders,
                    'total_items_sold' => $v->total_items_sold,
                ]);
            }
        }
        else {
            $this->info("MODE: Original (Cross-database Join)");
            // Executing the Cross-Database Join logic
            $results = Order::select([
                    'wc.country_short',
                    'wc.language_name',
                    DB::raw('COUNT(DISTINCT o.id) as total_orders'),
                    DB::raw('SUM(ol.qty) as total_items_sold')
                ])
                ->from($order_table.' as o')
                ->join('middleware_db.website_config as wc', 'o.locale', '=', 'wc.locale')
                ->join('order_lines as ol', 'o.id', '=', 'ol.order_id')
                ->where('wc.country_short', $country)
                ->groupBy('wc.country_short', 'wc.language_name')
                ->get();
        }
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
