<?php namespace Mohsin\Notify\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class UserAddTokenField extends Migration
{
    public function up()
    {
        if (Schema::hasColumns('users', ['fcm_token'])) {
            return;
        }

        Schema::table('users', function ($table) {
            $table->string('fcm_token')->default("");
        });
    }

    public function down()
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function ($table) {
                $table->dropColumn(['fcm_token']);
            });
        }
    }
}
