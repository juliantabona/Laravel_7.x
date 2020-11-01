<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGlobalVariablesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('global_variables', function (Blueprint $table) {

            $table->increments('id');

            /*  Global Variable Details  */
            $table->string('msisdn')->nullable();
            $table->boolean('test')->nullable()->default(false);
            $table->text('metadata')->nullable();

            /*  Ownership Information  */
            $table->unsignedInteger('project_id')->nullable();

            /*  Indexes  */
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
        Schema::dropIfExists('global_variables');
    }
}
