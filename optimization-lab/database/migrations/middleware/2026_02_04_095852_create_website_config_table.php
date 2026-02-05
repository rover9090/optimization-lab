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
            $table->string('country_short')->index();
            $table->string('language_name');
            $table->timestamps();
        });

        // Insert configuration data into middleware_db
        $configs = [
            ['locale' => 'ca-en', 'country_short' => 'ca', 'language_name' => 'English'],
            ['locale' => 'ca-fr', 'country_short' => 'ca', 'language_name' => 'French'],
            ['locale' => 'au-en', 'country_short' => 'au', 'language_name' => 'English'],
            ['locale' => 'au-cn', 'country_short' => 'au', 'language_name' => 'Chinese'],
            ['locale' => 'be-nl', 'country_short' => 'be', 'language_name' => 'Dutch'],
            ['locale' => 'be-fr', 'country_short' => 'be', 'language_name' => 'French'],
            ['locale' => 'fr', 'country_short' => 'fr', 'language_name' => 'French'],
            ['locale' => 'en', 'country_short' => 'en', 'language_name' => 'English'],
            ['locale' => 'cz', 'country_short' => 'cz', 'language_name' => 'Czech'],
            ['locale' => 'pl', 'country_short' => 'pl', 'language_name' => 'Polish'],
            ['locale' => 'hu', 'country_short' => 'hu', 'language_name' => 'Hungarian'],
            ['locale' => 'nl', 'country_short' => 'nl', 'language_name' => 'Dutch'],
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
