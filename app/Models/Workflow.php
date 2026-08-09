<?php

namespace App\Models;

use Database\Factories\WorkflowFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'description'])]
class Workflow extends Model
{
    /** @use HasFactory<WorkflowFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['archived_at' => 'datetime'];
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    /** @param Builder<Workflow> $query
     * @return Builder<Workflow>
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->whereBelongsTo($user);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<WorkflowNode, $this> */
    public function nodes(): HasMany
    {
        return $this->hasMany(WorkflowNode::class);
    }

    /** @return HasOne<WorkflowNode, $this> */
    public function rootNode(): HasOne
    {
        return $this->hasOne(WorkflowNode::class)->whereNull('parent_id');
    }

    /** @return HasMany<WorkflowActivity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(WorkflowActivity::class);
    }

    /** @return HasMany<WorkflowUndoEntry, $this> */
    public function undoEntries(): HasMany
    {
        return $this->hasMany(WorkflowUndoEntry::class);
    }
}
