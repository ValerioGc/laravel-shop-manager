<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shows', function (Blueprint $table) {
            $table->id();
            $table->string('label_ita');
            $table->string('label_eng');
            $table->string('location');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('description_ita')->nullable();
            $table->text('description_eng')->nullable();
            $table->string('link')->nullable();
            $table->unsignedBigInteger('image_id')->nullable();
            $table->timestamps();

            $table->foreign('image_id')->references('id')->on('images')->onDelete('set null');
            $table->unique(['label_ita', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shows');
    }
};
