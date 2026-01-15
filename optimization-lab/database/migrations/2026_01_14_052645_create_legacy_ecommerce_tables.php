<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. 語言與國家配置
        Schema::connection('middleware')->create('website_config', function ($table) {
            $table->id();
            $table->string('locale')->unique();
            $table->string('country_short');
            $table->timestamps();
        });

        // 2. 商品零件
        Schema::create('product_data', function ($table) {
            $table->id();
            $table->string('part_no')->unique();
            $table->string('description');
            $table->timestamps();
        });

        // 3. 訂單 (關聯 locale)
        Schema::create('orders', function ($table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('locale')->index(); 
            $table->datetime('order_date')->index();
            $table->timestamps();
        });

        // 4. 訂單明細
        Schema::create('order_lines', function ($table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained('product_data');
            $table->integer('qty');
            $table->timestamps();
        });

        // 5. 退貨表與明細 (合在一起寫方便示範)
        Schema::create('returns', function ($table) {
            $table->id();
            $table->foreignId('order_id')->constrained();
            $table->string('return_no')->unique();
            $table->timestamps();
        });

        Schema::create('return_lines', function ($table) {
            $table->id();
            $table->foreignId('return_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained('product_data');
            $table->integer('qty');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legacy_ecommerce_tables');
    }
};
