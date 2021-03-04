<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSessionNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('session_notifications', function (Blueprint $table) {

            $table->increments('id');

            /*  Session Notifications Details  */
            $table->string('session_id')->nullable();
            $table->string('msisdn')->nullable();
            $table->boolean('test')->nullable()->default(false);
            $table->string('type')->nullable();
            $table->text('message')->nullable();
            $table->text('metadata')->nullable();
            $table->boolean('showing_notification')->default(false);

            /*  Ownership Information  */
            $table->unsignedInteger('project_id')->nullable();

            /*  Indexes  */
            $table->index(['session_id']);
            $table->index(['msisdn', 'test', 'project_id']);

            /*  Timestamps  */
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('session_notifications');
    }
}
