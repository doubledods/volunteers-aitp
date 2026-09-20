<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTimegridSettingsToEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table)
        {
            // When false, the schedule shows the full day (12 am to midnight) like it always has
            $table->boolean('custom_timegrid')->default(false);

            // Optional manual bounds, stored as whole hours.
            // Start is 0-23 (0 = 12 am), end is 1-24 (24 = midnight at the end of the day).
            // Null means "work it out from the shifts".
            $table->unsignedTinyInteger('timegrid_start_hour')->nullable();
            $table->unsignedTinyInteger('timegrid_end_hour')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('events', function (Blueprint $table)
        {
            $table->dropColumn(['custom_timegrid', 'timegrid_start_hour', 'timegrid_end_hour']);
        });
    }
}