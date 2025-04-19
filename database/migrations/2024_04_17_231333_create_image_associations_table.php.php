<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
        public function up()
        {
            Schema::create('image_associations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('image_id');
                $table->tinyInteger('type_entity');
                $table->unsignedBigInteger('entity_id');
                $table->integer('order')->default(0);
                $table->timestamps();
    
                $table->foreign('image_id')->references('id')->on('images')->onDelete('cascade');

                $table->foreign('entity_id')->references('id')->on('products')->onDelete('cascade')->name('image_associations_product_id_foreign');
                $table->foreign('entity_id')->references('id')->on('shows')->onDelete('cascade')->name('image_associations_show_id_foreign');
            });
        }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
