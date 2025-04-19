<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up():void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->integer('quantity');
            $table->string('label_ita');
            $table->string('label_eng');
            $table->text('description_ita');
            $table->text('description_eng');
            $table->string('creator');
            $table->decimal('price', 10, 2)->nullable();
            $table->boolean('draft')->default(true);
            $table->boolean('in_evidence')->default(false);
            $table->boolean('deleting')->default(false);
            $table->integer('year')->nullable();
            $table->string('code')->nullable();

            $table->unsignedBigInteger('condition_id')->nullable();
            $table->timestamps();

            $table->foreign('condition_id')->references('id')->on('conditions')->onDelete('set null');;

        });
    }

    public function down():void
    {
        Schema::dropIfExists('products');
    }
};
