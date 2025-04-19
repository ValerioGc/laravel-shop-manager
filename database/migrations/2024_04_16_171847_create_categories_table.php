<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up():void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('label_ita');
            $table->string('label_eng');
            $table->integer('type')->default(0);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->timestamps();

            $table->foreign('parent_id')
                ->references('id')
                ->on('categories')
                ->nullOnDelete();

            $table->unique(['label_ita', 'label_eng', 'type', 'parent_id']);

        });
    }

    public function down():void
    {
        Schema::dropIfExists('categories');
    }
};
