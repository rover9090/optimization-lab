<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\WebsiteConfig;
use Illuminate\Support\Facades\Redis;

class ReportRegionalSales extends Command
{
    // The name and signature of the console command
    protected $signature = 'report:regional-sales 
                            {country : The country code to filter by, accepts "all" for no filtering}
                            {--optimized : Use the denormalized single-table query}
                            {--compare : Run both optimized and legacy logic for comparison}
                            {--cache : Use caching for website configuration}';

    // The console command description
    protected $description = 'Generate a sales report by joining data across multiple databases';

    public function handle()
    {
        $country = $this->argument('country');
        $useOptimized = $this->option('optimized');
        $useCompare = $this->option('compare');
        $useCache = $this->option('cache');
        $this->info("Fetching cross-database report for: " . strtoupper($country));

        // pre-warm cache for fair comparison
        Order::selectRaw('1')->limit(1)->get();
        // DB::select('SELECT 1 FROM `orders` LIMIT 1'); 
        // ^^^ this query cannot warmup the Eloquent model, just warm up the DB connection

        if ($useCompare) {
            // Run both and compare results
            $optimized = $this->runOptimized($country);
            $legacy = $this->runLegacy($country);
            $legacy_time = $this->displayModeReport('legacy', $legacy, 'yellow');
            $optimized_time = $this->displayModeReport('optimized', $optimized, 'green');
            $this->line("--------------------------------------------------");
            $gain = number_format($legacy_time / $optimized_time, 1);
            $this->line("<fg=cyan;options=bold>Performance Gain: {$gain}x faster.</>");
        }
        else {
            if($useOptimized) {
                $mode = 'optimized';
                $results = $this->runOptimized($country);
            } else {
                $mode = 'legacy';
                $results = $this->runLegacy($country);
            }

            $this->displayModeReport($mode, $results, 'green');
        }
    }

    private function runOptimized($country)
    {
        $_return = [
            'time' => 0,
            'sql' => '',
            'rows' => null,
            'Extra' => null,
            'type' => null,
            'filtered' => null,
            'results' => null,
            'country' => $country,
        ];
        $return = [$_return,$_return];
        Redis::ping(); // Ensure Redis warmup and connection before timing
        $start = microtime(true);

        $this->warn("MODE: Optimized (Separately scan)");

        $order_table = (new Order)->getTable();
        if($this->option('cache')){
            $website_configs_all = $this->getCacheWebsiteConfig($website_configs_query);
            $website_configs = ($country == 'all')?$website_configs_all:$website_configs_all->where('country_short', $country);
            $website_configs_sql = $website_configs_query?$website_configs_query->toSql():'Cache retrieval for website_config';
            $website_configs_explain = $website_configs_query?$website_configs_query?->explain()->toArray():null;
        }
        else{
            $website_configs_query = WebsiteConfig::select(
                    'country_short',
                    'language_name',
                    'locale'
                );
            if($country !== 'all') {
                $website_configs_query->where('country_short', $country);
            }
            $website_configs = $website_configs_query->get();
            $website_configs_sql = $website_configs_query->toSql();
            $website_configs_explain = $website_configs_query?->explain()->toArray();
        }
        $website_configs = $website_configs->keyBy('locale');
        $locales = $website_configs->pluck('locale');
        $results = collect([]);

        $stop1 = microtime(true);
        
        $query_results = Order::select([
                'o.locale',
                DB::raw('COUNT( o.id) as total_orders'),
                DB::raw('SUM(ol.qty) as total_items_sold')
            ])
            ->from($order_table.' as o')
            ->join('order_lines as ol', 'o.id', '=', 'ol.order_id')
            ->groupBy('o.locale');
        if($country !== 'all') {
            $query_results->whereIn('o.locale', $locales);
        }
        $query_results_sql = $query_results->toSql();
        $query_results_explain = $query_results?->explain();
        $query_results = $query_results->get();
        foreach($query_results as $v){
            // website_configs already keyby locale
            $config = $website_configs->get($v->locale);

            if (!$config) {
                $this->error("Missing config for locale: {$v->locale}");
                continue;
            }

            $results->push([
                'country_short'    => $config['country_short'],
                'language_name'    => $config['language_name'],
                'total_orders'     => $v->total_orders,
                'total_items_sold' => $v->total_items_sold,
            ]);
        }
        $stop2 = microtime(true);

        $return[0] = [
            'time' => number_format($stop1 - $start, 3),
            'sql' => $website_configs_sql,
            'explain' => $website_configs_explain,
        ];
        $return[1] = [
            'time' => number_format($stop2 - $stop1, 3),
            'sql' => $query_results_sql,
            'explain' => $query_results_explain,
            'results' => $results,
            'country' => $country,
        ];
        return $return;
    }

    private function runLegacy($country)
    {
        $_return = [
            'time' => 0,
            'sql' => '',
            'rows' => null,
            'Extra' => null,
            'type' => null,
            'filtered' => null,
            'results' => null,
            'country' => $country,
        ];
        $start = microtime(true);

        $this->info("MODE: Original (Cross-database Join)");

        $order_table = (new Order)->getTable();
        $middleware_db = config('database.connections.middleware.database');
        $results = Order::select([
                'wc.country_short',
                'wc.language_name',
                DB::raw('COUNT( o.id) as total_orders'),
                DB::raw('SUM(ol.qty) as total_items_sold')
            ])
            ->from($order_table.' as o')
            ->join($middleware_db.'.website_config as wc', 'o.locale', '=', 'wc.locale')
            ->join('order_lines as ol', 'o.id', '=', 'ol.order_id')
            ->groupBy('wc.country_short', 'wc.language_name');
        if($country !== 'all') {
            $results->where('wc.country_short', $country);
        }
        $results_sql = $results->toSql();
        $results_explain = $results?->explain()->toArray();
        $results = $results->get();
        $stop = microtime(true);
        $return = [[
            'time' => number_format($stop - $start, 3),
            'sql' => $results_sql,
            'explain' => $results_explain,
            'results' => $results,
            'country' => $country,
        ]];
        return $return;
    }

    private function displayModeReport(string $mode, array $profile, string $color = 'white')
    {
        $this->line("<fg={$color};options=bold>[MODE: " . strtoupper($mode) . "]</>");
        $return_time = 0;
        foreach($profile as $_profile) {
            $return_time += $_profile['time'];
            $this->line("Execution Time: <fg=gray>{$_profile['time']}s</>");
            $this->line("Query Logic: <fg=gray>\"{$_profile['sql']}\"</>");
            
            foreach($_profile['explain'] ?? [] as $explain) {
                $planInfo = $explain->rows ?? 'N/A';
                $extra = $explain->Extra ?? 'Using Index';
                $type = $explain->type ?? 'N/A';
                $filtered = $explain->filtered ?? 'N/A';
                $ref = $explain->ref ?? 'N/A';
                $this->line("Database Plan: <fg=gray>[{$extra}; Scanning {$planInfo} rows; Type: {$type}; Filtered: {$filtered}%; Ref: {$ref}]</>");
            }
            $this->line("");

            if(isset($_profile['results'])) {
                if ($_profile['results']->isEmpty()) {
                    $this->error("No data found for country: {$_profile['country']}");
                } else {
                    $this->warn("total Time: {$return_time} seconds");
                    $this->table(
                        ['Country', 'Language', 'Order Count', 'Items Sold'],
                        $_profile['results']->toArray()
                    );
                }
            }
        }

        return $return_time;
    }

    private function getCacheWebsiteConfig(&$query=null)
    {
        if($website_config = Redis::get('website_config')) {
            return collect(json_decode($website_config,true));
        }
        $query = WebsiteConfig::select('id','locale','country_short','language_name');
        $website_config = $query->get()->toArray();
        redis::set('website_config', json_encode($website_config), 'EX', 300);
        return collect($website_config);
    }
}