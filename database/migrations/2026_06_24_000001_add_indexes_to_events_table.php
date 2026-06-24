<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The listing/visual queries all filter on a column and then order by
     * `created_time`. These composite indexes let MySQL satisfy the filter and
     * the sort from a single index (no filesort over 1.25M rows), and the
     * lat/lng index backs the map's bounding-box queries.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Status filter + default ordering by start time.
            $table->index(['status', 'created_time'], 'events_status_created_time_index');
            // Category filter + ordering (card grid).
            $table->index(['type', 'created_time'], 'events_type_created_time_index');
            // Date-range scans without a status/type filter.
            $table->index('created_time', 'events_created_time_index');
            // Bounding-box lookups for the map.
            $table->index(['latitude', 'longitude'], 'events_lat_lng_index');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_status_created_time_index');
            $table->dropIndex('events_type_created_time_index');
            $table->dropIndex('events_created_time_index');
            $table->dropIndex('events_lat_lng_index');
        });
    }
};
