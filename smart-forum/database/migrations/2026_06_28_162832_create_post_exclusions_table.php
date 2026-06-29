<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('post_exclusions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('excluded_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            // Prevent duplicate exclusions
            $table->unique(['post_id', 'excluded_user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('post_exclusions');
    }
};