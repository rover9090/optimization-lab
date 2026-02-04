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
        // 2. Insert products into optimization_lab
        $this->command->info('Generating product data...');
        for ($i = 0; $i < 50; $i++) {
            DB::table('product_data')->insert([
                'part_no' => 'PART-' . Str::upper(Str::random(8)),
                'description' => 'Global Component ' . $i,
            ]);
        }

        // 3. Generate large-scale orders (starting with 100,000 records)
        $productIds = DB::table('product_data')->pluck('id')->toArray();
        $locales = ['ca-en', 'ca-fr', 'au-en'];

        $this->command->info('Generating 100,000 cross-border orders...');
        
        for ($i = 0; $i < 100000; $i++) {
            $orderId = DB::table('orders')->insertGetId([
                'order_number' => 'ORD-' . strtoupper(Str::random(10)),
                'locale' => $locales[array_rand($locales)],
                'order_date' => now()->subDays(rand(1, 365)),
            ]);

            // Each order contains 1-2 line items
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
        $this->command->info('Data seeding completed successfully!');
    }
}