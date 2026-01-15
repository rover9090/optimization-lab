<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EcommerceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. 往 middleware_db 塞入配置資料
        $this->command->info('正在初始化 Middleware 配置...');
        $configs = [
            ['locale' => 'ca-en', 'country_short' => 'ca', 'language_name' => 'English'],
            ['locale' => 'ca-fr', 'country_short' => 'ca', 'language_name' => 'French'],
            ['locale' => 'au-en', 'country_short' => 'au', 'language_name' => 'English'],
        ];
        // 注意這裡用了 connection('middleware')
        DB::connection('middleware')->table('website_config')->upsert($configs, ['locale']);

        // 2. 往 optimization_lab 塞入商品
        $this->command->info('正在生成商品資料...');
        for ($i = 0; $i < 50; $i++) {
            DB::table('product_data')->insert([
                'part_no' => 'PART-' . Str::upper(Str::random(8)),
                'description' => 'Global Component ' . $i,
            ]);
        }

        // 3. 生成大量訂單 (5000 筆作為起點)
        $productIds = DB::table('product_data')->pluck('id')->toArray();
        $locales = ['ca-en', 'ca-fr', 'au-en'];

        $this->command->info('正在生成 5000 筆跨境訂單...');
        
        for ($i = 0; $i < 5000; $i++) {
            $orderId = DB::table('orders')->insertGetId([
                'order_number' => 'ORD-' . strtoupper(Str::random(10)),
                'locale' => $locales[array_rand($locales)],
                'order_date' => now()->subDays(rand(1, 365)),
            ]);

            // 每筆訂單 1-2 筆明細
            $lines = [];
            foreach ((array)array_rand($productIds, rand(1, 2)) as $pIdIndex) {
                $lines[] = [
                    'order_id' => $orderId,
                    'product_id' => $productIds[$pIdIndex],
                    'qty' => rand(1, 5),
                    'created_at' => now(),
                ];
            }
            DB::table('order_lines')->insert($lines);
        }
        $this->command->info('數據填充完成！');
    }
}