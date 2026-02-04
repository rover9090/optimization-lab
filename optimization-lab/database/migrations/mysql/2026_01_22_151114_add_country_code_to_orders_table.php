<?php

/**
 * TECHNICAL DESIGN NOTE: DEFERRED DENORMALIZATION (PHASE 2)
 * * STATUS: INACTIVE (PROPOSED)
 * * RATIONALE:
 * While denormalizing 'country_code' into the 'orders' table provides the 
 * highest query performance, it introduces data redundancy and risk of 
 * long table locks during deployment on 10M+ row tables.
 * * DECISION:
 * We currently utilize "Application-side Joins" in the Reporting Service 
 * to maintain architectural decoupling. This migration is prepared as a 
 * "Quick-Scale" option if database-level bottlenecks occur in the future.
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        /*
        // 1. Add the redundant column for optimization (Denormalization)
        Schema::table('orders', function (Blueprint $table) {
            // We add 'country_code' to avoid cross-database joins
            $table->string('country_code', 2)->nullable()->after('locale')->index();
        });

        // 2. Sync data from the external database (Data Patching)
        // We fetch the mapping from middleware_db.website_config
        $configMap = DB::connection('middleware')
            ->table('website_config')
            ->pluck('country_short', 'locale');

        // Update the 100,000+ existing records in the orders table
        foreach ($configMap as $locale => $country) {
            DB::table('orders')
                ->where('locale', $locale)
                ->update(['country_code' => $country]);
        }
        */
    }

    public function down(): void
    {
        /*
        Schema::table('orders', function (Blueprint $table) {
            // 1. Drop the index first (Good practice for some DB engines)
            $table->dropIndex(['country_code']);
            
            // 2. Drop the redundant column
            $table->dropColumn('country_code');
        });
        */
    }
};
