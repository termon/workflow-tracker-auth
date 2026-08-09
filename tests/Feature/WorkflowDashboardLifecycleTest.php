<?php

use App\Actions\CreateWorkflow;
use App\Models\Task;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

test('dashboard only contains workflows owned by the authenticated user in each lifecycle state', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    Workflow::factory()->for($userA)->create(['name' => 'A active']);
    Workflow::factory()->for($userA)->create(['name' => 'A archived', 'archived_at' => now()]);
    Workflow::factory()->for($userA)->create(['name' => 'A deleted'])->delete();

    Workflow::factory()->for($userB)->create(['name' => 'B active']);
    Workflow::factory()->for($userB)->create(['name' => 'B archived', 'archived_at' => now()]);
    Workflow::factory()->for($userB)->create(['name' => 'B deleted'])->delete();

    $this->actingAs($userA)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('activeWorkflows.0.name', 'A active')
            ->where('archivedWorkflows.0.name', 'A archived')
            ->where('recycledWorkflows.0.name', 'A deleted')
            ->missing('activeWorkflows.1')
            ->missing('archivedWorkflows.1')
            ->missing('recycledWorkflows.1'));
});

test('an owner can rename archive restore recycle recover and permanently delete a workflow', function () {
    $user = User::factory()->create();
    $workflow = (new CreateWorkflow)->handle($user, 'Original');

    $this->actingAs($user)
        ->patch(route('workflows.rename', $workflow), ['name' => 'Renamed'])
        ->assertRedirect(route('dashboard'));
    expect($workflow->fresh()->name)->toBe('Renamed');

    $this->post(route('workflows.archive', $workflow))->assertRedirect(route('dashboard'));
    expect($workflow->fresh()->archived_at)->not->toBeNull();

    $this->post(route('workflows.unarchive', $workflow))->assertRedirect(route('dashboard'));
    expect($workflow->fresh()->archived_at)->toBeNull();

    $this->delete(route('workflows.destroy', $workflow))->assertRedirect(route('dashboard'));
    $this->assertSoftDeleted($workflow);

    $this->post(route('workflows.restore', $workflow->id))->assertRedirect(route('dashboard'));
    $this->assertNotSoftDeleted($workflow);

    $workflow->delete();
    $this->delete(route('workflows.force-delete', $workflow->id))->assertRedirect(route('dashboard'));
    $this->assertModelMissing($workflow);
});

test('duplicating a workflow keeps ownership and structure while resetting task progress', function () {
    $user = User::factory()->create();
    $source = (new CreateWorkflow)->handle($user, 'Research plan');
    $group = $source->nodes()->create([
        'parent_id' => $source->rootNode->id,
        'type' => 'group',
        'title' => 'Draft',
        'position' => 0,
        'filesystem_key' => Str::uuid(),
    ]);
    $group->tasks()->create([
        'title' => 'Write outline',
        'status' => 'completed',
        'completed_at' => now(),
        'position' => 0,
        'filesystem_key' => Str::uuid(),
    ]);

    $this->actingAs($user)
        ->post(route('workflows.duplicate', $source))
        ->assertRedirect(route('dashboard'));

    $duplicate = Workflow::query()->whereKeyNot($source->id)->firstOrFail();
    $duplicateTask = Task::query()
        ->whereHas('group', fn ($query) => $query->whereBelongsTo($duplicate))
        ->firstOrFail();

    expect($duplicate->user_id)->toBe($user->id)
        ->and($duplicate->name)->toBe('Research plan copy')
        ->and($duplicate->nodes)->toHaveCount(2)
        ->and($duplicateTask->status)->toBe('not_started')
        ->and($duplicateTask->completed_at)->toBeNull();
});

test('another user cannot perform any workflow lifecycle mutation', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $workflow = (new CreateWorkflow)->handle($userB, 'User B workflow');

    $this->actingAs($userA);

    $this->patch(route('workflows.rename', $workflow), ['name' => 'Compromised'])->assertForbidden();
    $this->post(route('workflows.duplicate', $workflow))->assertForbidden();
    $this->post(route('workflows.archive', $workflow))->assertForbidden();

    $workflow->forceFill(['archived_at' => now()])->save();
    $this->post(route('workflows.unarchive', $workflow))->assertForbidden();
    $this->delete(route('workflows.destroy', $workflow))->assertForbidden();

    $workflow->delete();
    $this->post(route('workflows.restore', $workflow->id))->assertForbidden();
    $this->delete(route('workflows.force-delete', $workflow->id))->assertForbidden();

    expect($workflow->fresh()->name)->toBe('User B workflow');
});

test('database seeding creates the standard verification account', function () {
    $this->seed();

    $user = User::query()->where('email', 'test@tracker.com')->firstOrFail();

    expect($user->email_verified_at)->not->toBeNull()
        ->and(Hash::check('password', $user->password))->toBeTrue();
});
