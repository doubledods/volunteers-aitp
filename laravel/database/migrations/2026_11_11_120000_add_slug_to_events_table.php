<?php

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSlugToEventsTable extends Migration
{
    /**
     * Add a URL slug to events and fill it in for existing events (oldest first,
     * including soft-deleted ones so slugs never collide).
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table)
        {
            $table->string('slug')->nullable()->after('name');
        });

        $taken = [];

        foreach(DB::table('events')->orderBy('id')->get() as $event)
        {
            $base = Str::slug($event->name);

            // Never let a slug be all digits, because /roster/123 means "event id 123"
            if($base === '' || ctype_digit($base))
            {
                $base = trim('event-' . $base, '-');
            }

            $slug = $base;

            // Same name as an earlier event? Add the start year first (e.g. -2027)
            if(isset($taken[$slug]) && $event->start_date)
            {
                $slug = $base . '-' . substr($event->start_date, 0, 4);
            }

            // Still taken? Add a counter
            $candidate = $slug;
            $counter = 2;

            while(isset($taken[$candidate]))
            {
                $candidate = $slug . '-' . $counter++;
            }

            $taken[$candidate] = true;
            DB::table('events')->where('id', $event->id)->update(['slug' => $candidate]);
        }

        Schema::table('events', function (Blueprint $table)
        {
            $table->unique('slug');
        });
    }

    public function down()
    {
        Schema::table('events', function (Blueprint $table)
        {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
}
