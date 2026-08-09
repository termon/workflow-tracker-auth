<?php

namespace App\Http\Controllers;

use App\Actions\CreateWorkflow;
use App\Actions\DuplicateWorkflow;
use App\Http\Requests\RenameWorkflowRequest;
use App\Http\Requests\StoreWorkflowRequest;
use App\Http\Requests\UpdateWorkflowRequest;
use App\Models\Workflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WorkflowController extends Controller
{
    public function dashboard(Request $request): Response
    {
        Gate::authorize('viewAny', Workflow::class);

        $ownedWorkflows = Workflow::query()->ownedBy($request->user());

        return Inertia::render('Dashboard', [
            'activeWorkflows' => (clone $ownedWorkflows)
                ->whereNull('archived_at')
                ->latest()
                ->get(),
            'archivedWorkflows' => (clone $ownedWorkflows)
                ->whereNotNull('archived_at')
                ->latest('archived_at')
                ->get(),
            'recycledWorkflows' => Workflow::onlyTrashed()
                ->ownedBy($request->user())
                ->latest('deleted_at')
                ->get(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Workflow::class);

        return response()->json([
            'data' => Workflow::query()
                ->ownedBy($request->user())
                ->latest()
                ->get(),
        ]);
    }

    public function store(StoreWorkflowRequest $request, CreateWorkflow $createWorkflow): JsonResponse|RedirectResponse
    {
        $workflow = $createWorkflow->handle(
            $request->user(),
            $request->validated('name'),
            $request->validated('description'),
        );

        if ($request->expectsJson()) {
            return response()->json(['data' => $workflow], 201);
        }

        return to_route('dashboard')->with('status', 'Workflow created.');
    }

    public function show(Workflow $workflow): JsonResponse
    {
        Gate::authorize('view', $workflow);

        return response()->json(['data' => $workflow->load('rootNode')]);
    }

    public function update(UpdateWorkflowRequest $request, Workflow $workflow): JsonResponse|RedirectResponse
    {
        $workflow->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['data' => $workflow->fresh()]);
        }

        return to_route('dashboard')->with('status', 'Workflow updated.');
    }

    public function rename(RenameWorkflowRequest $request, Workflow $workflow): RedirectResponse
    {
        $workflow->update($request->validated());

        return to_route('dashboard')->with('status', 'Workflow renamed.');
    }

    public function duplicate(Request $request, Workflow $workflow, DuplicateWorkflow $duplicateWorkflow): RedirectResponse
    {
        Gate::authorize('view', $workflow);
        $duplicateWorkflow->handle($request->user(), $workflow);

        return to_route('dashboard')->with('status', 'Workflow duplicated.');
    }

    public function archive(Workflow $workflow): RedirectResponse
    {
        Gate::authorize('update', $workflow);
        $workflow->forceFill(['archived_at' => now()])->save();

        return to_route('dashboard')->with('status', 'Workflow archived.');
    }

    public function unarchive(Workflow $workflow): RedirectResponse
    {
        Gate::authorize('update', $workflow);
        $workflow->forceFill(['archived_at' => null])->save();

        return to_route('dashboard')->with('status', 'Workflow restored.');
    }

    public function destroy(Workflow $workflow): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $workflow);
        $workflow->delete();

        if (request()->expectsJson()) {
            return response()->json(status: 204);
        }

        return to_route('dashboard')->with('status', 'Workflow moved to the recycle bin.');
    }

    public function restore(Workflow $workflow): RedirectResponse
    {
        Gate::authorize('restore', $workflow);
        $workflow->restore();

        return to_route('dashboard')->with('status', 'Workflow restored from the recycle bin.');
    }

    public function forceDelete(Workflow $workflow): RedirectResponse
    {
        Gate::authorize('forceDelete', $workflow);
        $workflow->forceDelete();

        return to_route('dashboard')->with('status', 'Workflow permanently deleted.');
    }
}
