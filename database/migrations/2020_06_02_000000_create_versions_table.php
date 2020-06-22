<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVersionsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('versions', function (Blueprint $table) {

            $table->increments('id');

            /*  Version Details  */
            $table->float('number')->nullable();
            $table->string('description', 500)->nullable();

            /*  Builder  */
            $table->json('builder')->nullable();

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
        Schema::dropIfExists('versions');
    }
}
