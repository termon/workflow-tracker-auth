<?php

namespace App\Actions;

use App\Models\User;
use App\Models\Workflow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateWorkflow
{
    public function handle(User $owner, string $name, ?string $description = null): Workflow
    {
        return DB::transaction(function () use ($owner, $name, $description): Workflow {
            $workflow = $owner->workflows()->create([
                'name' => $name,
                'description' => $description,
            ]);

            $workflow->nodes()->create([
                'type' => 'container',
                'title' => $name,
                'position' => 0,
                'ordering_mode' => 'unordered',
                'filesystem_key' => Str::uuid(),
            ]);

            return $workflow->load('rootNode');
        });
    }
}
