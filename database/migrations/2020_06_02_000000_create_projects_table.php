<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProjectsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {

            $table->increments('id');

            /*  Project Details  */
            $table->string('name')->nullable();
            $table->string('description', 500)->nullable();
            $table->char('hex_color', 6)->default('2D8CF0');
            $table->boolean('online')->nullable()->default(false);
            $table->string('offline_message')->nullable();
            $table->unsignedInteger('active_version_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();

            /*  Timestamps  */
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('projects');
    }
}
