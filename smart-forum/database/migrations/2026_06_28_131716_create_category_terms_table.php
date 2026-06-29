<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('category_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->string('term');
            $table->string('category');
            $table->integer('frequency')->default(1);
            $table->timestamps();
            $table->unique(['term', 'group_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('category_terms');
    }
};