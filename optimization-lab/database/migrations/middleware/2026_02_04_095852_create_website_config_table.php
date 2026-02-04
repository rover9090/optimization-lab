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
        // Create the configuration table in the middleware database
        Schema::create('website_config', function (Blueprint $table) {
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

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_config');
    }
};
