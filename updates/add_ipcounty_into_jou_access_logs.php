<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Jou\AccessLog\Models\AccessLog as AccessLogModel;
class AddDescribeIntoProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //AccessLogModel::where('user_agent','like','%Android%')->update(['device'=>'android']);

        Schema::table('jou_access_logs', function (Blueprint $table) {
            $table->string('ipcountry')->nullable()->after('ip');
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
            $table->dropColumn('ipcountry');
        });
    }
}
