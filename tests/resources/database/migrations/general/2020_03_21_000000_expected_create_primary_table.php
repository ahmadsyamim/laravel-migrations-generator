<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use KitLoong\MigrationsGenerator\Tests\TestMigration;

return new class extends TestMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('primary_id', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->primary('id');
        });

        Schema::create('primary_name', function (Blueprint $table) {
            $table->string('name');
            $table->primary('name', 'primary_custom');
        });

        Schema::create('signed_primary_id', function (Blueprint $table) {
            $table->integer('id');
            $table->primary('id');
        });

        Schema::create('composite_primary', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->unsignedInteger('sub_id');
            $table->primary(['id', 'sub_id']);
        });

        // Test short table name
        Schema::create('s', function (Blueprint $table) {
            $table->bigIncrements('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('primary_id');
        Schema::dropIfExists('primary_name');
        Schema::dropIfExists('signed_primary_id');
        Schema::dropIfExists('composite_primary');
        Schema::dropIfExists('s');
    }
};
