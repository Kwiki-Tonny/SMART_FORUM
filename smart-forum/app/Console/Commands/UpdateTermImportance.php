<?php

namespace App\Console\Commands;

use App\Models\Group;
use App\Services\MLTextClassifier;
use Illuminate\Console\Command;

class UpdateTermImportance extends Command
{
    protected $signature = 'ml:update-importance {--group= : Specific group ID}';
    protected $description = 'Update term importance (IDF) for all groups or a specific group';

    public function handle()
    {
        $groupId = $this->option('group');

        if ($groupId) {
            $groups = Group::where('id', $groupId)->get();
        } else {
            $groups = Group::all();
        }

        foreach ($groups as $group) {
            $this->info("Processing group: {$group->name} (ID: {$group->id})");
            MLTextClassifier::recalculateImportance($group->id);
        }

        $this->info('Term importance updated successfully!');
    }
}