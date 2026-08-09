<?php

namespace App\Actions;

use App\Models\Task;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowNode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DuplicateWorkflow
{
    public function handle(User $owner, Workflow $source): Workflow
    {
        return DB::transaction(function () use ($owner, $source): Workflow {
            $duplicate = $owner->workflows()->create([
                'name' => $source->name.' copy',
                'description' => $source->description,
            ]);

            $this->copyNode($source->rootNode()->firstOrFail(), $duplicate, null);

            return $duplicate->load('rootNode');
        });
    }

    private function copyNode(WorkflowNode $source, Workflow $duplicate, ?WorkflowNode $parent): WorkflowNode
    {
        $copy = $duplicate->nodes()->create([
            'parent_id' => $parent?->id,
            'type' => $source->type,
            'title' => $source->title,
            'position' => $source->position,
            'ordering_mode' => $source->ordering_mode,
            'filesystem_key' => Str::uuid(),
        ]);

        $source->tasks()->oldest('position')->each(function (Task $task) use ($copy): void {
            $copy->tasks()->create([
                'title' => $task->title,
                'creates_document_section' => $task->creates_document_section,
                'description' => $task->description,
                'notes' => $task->notes,
                'document_markdown' => $task->document_markdown,
                'status' => 'not_started',
                'blocked_reason' => null,
                'priority' => $task->priority,
                'due_date' => $task->due_date,
                'completed_at' => null,
                'position' => $task->position,
                'filesystem_key' => Str::uuid(),
            ]);
        });

        $source->children()->oldest('position')->each(
            fn (WorkflowNode $child): WorkflowNode => $this->copyNode($child, $duplicate, $copy),
        );

        return $copy;
    }
}
