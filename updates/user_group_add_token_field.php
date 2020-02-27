<?php namespace Mohsin\Notify\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class UserGroupAddTokenField extends Migration
{
    public function up()
    {
        if (Schema::hasColumns('user_groups', ['fcm_token', 'fcm_tokens'])) {
            return;
        }

        Schema::table('user_groups', function ($table) {
            $table->string('fcm_token')->after('description')->default("");
            $table->longText('fcm_tokens')->after('fcm_token')->default("");
        });
    }

    public function down()
    {
        if (Schema::hasTable('user_groups')) {
            Schema::table('user_groups', function ($table) {
                $table->dropColumn(['fcm_token', 'fcm_tokens']);
            });
        }
    }
}
