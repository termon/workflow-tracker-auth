<?php

use App\Models\User;
use App\Models\Workflow;

test('workflow routes require authentication', function () {
    $workflow = Workflow::factory()->create();

    $this->getJson(route('workflows.index'))->assertUnauthorized();
    $this->getJson(route('workflows.show', $workflow))->assertUnauthorized();
    $this->postJson(route('workflows.store'), ['name' => 'Private'])->assertUnauthorized();
    $this->patchJson(route('workflows.update', $workflow), ['name' => 'Changed'])->assertUnauthorized();
    $this->deleteJson(route('workflows.destroy', $workflow))->assertUnauthorized();
});

test('users only see their own workflows in the index', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $workflowA = Workflow::factory()->for($userA)->create();
    $workflowB = Workflow::factory()->for($userB)->create();

    $this->actingAs($userA)
        ->getJson(route('workflows.index'))
        ->assertOk()
        ->assertJsonPath('data.0.id', $workflowA->id)
        ->assertJsonMissing(['id' => $workflowB->id]);
});

test('created workflows belong to the authenticated user and ignore injected ownership', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $response = $this->actingAs($userA)->postJson(route('workflows.store'), [
        'name' => 'Owned workflow',
        'description' => 'Private plan',
        'user_id' => $userB->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.user_id', $userA->id)
        ->assertJsonPath('data.root_node.title', 'Owned workflow');

    $workflow = Workflow::firstOrFail();
    expect($workflow->user_id)->toBe($userA->id)
        ->and($workflow->rootNode)->not->toBeNull();
});

test('owners can view update and delete their workflow', function () {
    $user = User::factory()->create();
    $workflow = Workflow::factory()->for($user)->create();

    $this->actingAs($user)
        ->getJson(route('workflows.show', $workflow))
        ->assertOk()
        ->assertJsonPath('data.id', $workflow->id);

    $this->patchJson(route('workflows.update', $workflow), ['name' => 'Renamed'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Renamed');

    $this->deleteJson(route('workflows.destroy', $workflow))->assertNoContent();
    $this->assertSoftDeleted($workflow);
});

test('users cannot view or mutate another users workflow', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $workflowB = Workflow::factory()->for($userB)->create(['name' => 'User B workflow']);

    $this->actingAs($userA)
        ->getJson(route('workflows.show', $workflowB))
        ->assertForbidden();

    $this->patchJson(route('workflows.update', $workflowB), ['name' => 'Compromised'])
        ->assertForbidden();

    $this->deleteJson(route('workflows.destroy', $workflowB))
        ->assertForbidden();

    expect($workflowB->fresh()->name)->toBe('User B workflow')
        ->and($workflowB->fresh()->deleted_at)->toBeNull();
});
