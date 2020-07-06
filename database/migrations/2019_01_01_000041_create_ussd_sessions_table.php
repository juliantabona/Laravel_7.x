<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUssdSessionsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('ussd_sessions', function (Blueprint $table) {

            $table->increments('id');

            /*  Session Details  */
            $table->string('session_id')->nullable();
            $table->string('service_code')->nullable();
            $table->string('type')->default('shared');
            $table->string('msisdn')->nullable();
            $table->string('request_type')->default(1);
            $table->string('text')->nullable();
            $table->string('status')->nullable();
            $table->boolean('allow_timeout')->nullable()->default(0);
            $table->timestampTz('timeout_at')->nullable();

            /*  Meta Data  */
            $table->json('metadata')->nullable();

            /*  Ownership Information  */
            $table->unsignedInteger('owner_id')->nullable();
            $table->string('owner_type')->nullable();

            /*  Timestamps  */
            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('ussd_sessions');
    }
}
