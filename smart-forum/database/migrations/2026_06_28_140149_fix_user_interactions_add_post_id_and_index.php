<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        $table = 'user_interactions';

        // 1. Add post_id column if it doesn't exist
        if (!Schema::hasColumn($table, 'post_id')) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('post_id')
                      ->nullable()
                      ->after('topic_id')
                      ->constrained('posts')
                      ->onDelete('cascade');
            });
        }

        // 2. Add the composite index if it doesn't exist
        $indexName = 'idx_user_post_action';
        $indexExists = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);

        if (empty($indexExists)) {
            Schema::table($table, function (Blueprint $table) use ($indexName) {
                $table->index(['user_id', 'post_id', 'action_type'], $indexName);
            });
        }
    }

    public function down()
    {
        $table = 'user_interactions';
        $indexName = 'idx_user_post_action';

        // Drop the index
        $indexExists = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        if (!empty($indexExists)) {
            Schema::table($table, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }

        // Drop the post_id column if it exists
        if (Schema::hasColumn($table, 'post_id')) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropForeign(['post_id']);
                $table->dropColumn('post_id');
            });
        }
    }
};