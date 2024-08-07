<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeCreatedAtKeyIntoJouAccessLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('jou_access_logs', function (Blueprint $table) {
            $table->index('device');
            $table->index('method');
            $table->index('crawler');


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('jou_access_logs', function (Blueprint $table) {
            $table->dropIndex('jou_access_logs_device_index');
            $table->dropIndex('jou_access_logs_method_index');
            $table->dropIndex('jou_access_logs_crawler_index');
        });
    }
}
