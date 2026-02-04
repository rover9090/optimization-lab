<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create the configuration table in the middleware database
        Schema::connection('middleware')->create('website_config', function (Blueprint $table) {
            $table->id();
            $table->string('locale')->unique();
            $table->string('country_short');
            $table->string('language_name');
            $table->timestamps();
        });

        // Insert configuration data into middleware_db
        $configs = [
            ['locale' => 'ca-en', 'country_short' => 'ca', 'language_name' => 'English'],
            ['locale' => 'ca-fr', 'country_short' => 'ca', 'language_name' => 'French'],
            ['locale' => 'au-en', 'country_short' => 'au', 'language_name' => 'English'],
        ];
        // Note: Explicitly using the 'middleware' connection here
        DB::connection('middleware')->table('website_config')->upsert($configs, ['locale']);

        // 2. Product Parts (Inventory data)
        Schema::create('product_data', function (Blueprint $table) {
            $table->id();
            $table->string('part_no')->unique();
            $table->string('description');
            $table->timestamps();
        });

        // 3. Orders (Associated with locale for regional tracking)
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('locale')->index(); 
            $table->datetime('order_date')->index();
            $table->timestamps();
        });

        // 4. Order Line Items
        Schema::create('order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained('product_data');
            $table->integer('qty');
            $table->timestamps();
        });

        // 5. Returns Table (Combined with details for demonstration purposes)
        Schema::create('returns', function ($table) {
            $table->id();
            $table->foreignId('order_id')->constrained();
            $table->string('return_no')->unique();
            $table->timestamps();
        });

        // 6. Return Line Items
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
        Schema::dropIfExists('return_lines');
        Schema::dropIfExists('returns');
        Schema::dropIfExists('order_lines');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('product_data');
        Schema::connection('middleware')->dropIfExists('website_config');
    }
};
