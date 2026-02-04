<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderLine;
use App\Models\WebsiteConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Artisan;

class ReportRegionalSalesTest extends TestCase
{
    use DatabaseTransactions;
    protected $connectionsToTransact = [
        'mysql',
        'middleware'
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Order::query()->delete();
        OrderLine::query()->delete();
    }

    #[Test]
    public function it_calculates_sales_correctly_with_optimized_logic()
    {
        Order::factory()
            ->count(10)
            ->has(OrderLine::factory()->count(3), 'orderLine')
            ->create(['locale' => 'au-en']);
        $orders = Order::factory()
            ->count(3)
            ->has(OrderLine::factory()->count(2), 'orderLine')
            ->create(['locale' => 'ca-fr']);
        $cafr_totalQty = $orders->flatMap->orderLine->sum('qty');
        $orders = Order::factory()
            ->count(2)
            ->has(OrderLine::factory()->count(3), 'orderLine')
            ->create(['locale' => 'ca-en']);
        $caen_totalQty = $orders->flatMap->orderLine->sum('qty');
        
        Artisan::call('report:regional-sales ca --optimized');
        $output = Artisan::output();
        // dump($output);
        $this->assertStringContainsString('| ca      | French   | 3           | '.$cafr_totalQty, $output);
        $this->assertStringContainsString('| ca      | English  | 2           | '.$caen_totalQty, $output);
    }
}
