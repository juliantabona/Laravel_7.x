<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShortCodesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('short_codes', function (Blueprint $table) {

            $table->increments('id');

            /*  Service Code Details  */
            $table->string('shared_code')->nullable();
            $table->string('dedicated_code')->nullable();
            $table->string('country')->nullable();

            /*  Ownership Information  */
            $table->unsignedInteger('project_id')->nullable();

            /*  Timestamps  */
            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('short_codes');
    }
}
