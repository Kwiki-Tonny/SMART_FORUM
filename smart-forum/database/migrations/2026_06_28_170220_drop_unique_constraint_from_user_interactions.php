<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        $table = 'user_interactions';
        $indexName = 'user_interactions_user_id_topic_id_action_type_unique';

        // Check if the index exists
        $result = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);

        if (!empty($result)) {
            DB::statement("ALTER TABLE {$table} DROP INDEX {$indexName}");
            echo "Dropped unique index: {$indexName}\n";
        } else {
            echo "Index {$indexName} does not exist. Skipping.\n";
        }
    }

    public function down()
    {
        // Recreate the unique index if needed (though we recommend not to)
        $table = 'user_interactions';
        $indexName = 'user_interactions_user_id_topic_id_action_type_unique';

        $result = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);

        if (empty($result)) {
            DB::statement("ALTER TABLE {$table} ADD UNIQUE INDEX {$indexName} (user_id, topic_id, action_type)");
            echo "Recreated unique index: {$indexName}\n";
        }
    }
};