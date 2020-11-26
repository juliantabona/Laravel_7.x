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
            $table->mediumText('reply_records')->nullable();
            $table->mediumText('logs')->nullable();
            $table->boolean('fatal_error')->nullable()->default(false);
            $table->string('fatal_error_msg', 500)->nullable();
            $table->boolean('test')->nullable()->default(false);
            $table->boolean('allow_timeout')->nullable()->default(0);
            $table->timestampTz('timeout_at')->nullable();
            $table->decimal('estimated_record_size', 8, 2)->default(0.00);
            $table->unsignedMediumInteger('total_session_duration')->default(0);
            $table->text('user_response_durations')->nullable();
            $table->text('session_execution_times')->nullable();
            $table->text('estimated_record_sizes')->nullable();

            /*  Meta Data  */
            $table->json('metadata')->nullable();

            /*  Ownership Information  */
            $table->unsignedInteger('project_id')->nullable();
            $table->unsignedInteger('version_id')->nullable();

            /*  Indexes  */
            $table->index(['session_id', 'test']);
            $table->index(['msisdn', 'test', 'project_id', 'created_at']);


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
