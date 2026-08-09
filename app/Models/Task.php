<?php

namespace App\Models;

use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'title',
    'creates_document_section',
    'description',
    'notes',
    'document_markdown',
    'status',
    'blocked_reason',
    'priority',
    'due_date',
    'completed_at',
    'position',
    'filesystem_key',
])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'creates_document_section' => true,
        'status' => 'not_started',
        'priority' => 'medium',
    ];

    protected function casts(): array
    {
        return [
            'creates_document_section' => 'boolean',
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<WorkflowNode, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(WorkflowNode::class, 'workflow_node_id');
    }
}
