<?php

namespace App\Http\Controllers;

use App\Actions\CreateWorkflow;
use App\Http\Requests\StoreWorkflowRequest;
use App\Http\Requests\UpdateWorkflowRequest;
use App\Models\Workflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WorkflowController extends Controller
{
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

    public function store(StoreWorkflowRequest $request, CreateWorkflow $createWorkflow): JsonResponse
    {
        $workflow = $createWorkflow->handle(
            $request->user(),
            $request->validated('name'),
            $request->validated('description'),
        );

        return response()->json(['data' => $workflow], 201);
    }

    public function show(Workflow $workflow): JsonResponse
    {
        Gate::authorize('view', $workflow);

        return response()->json(['data' => $workflow->load('rootNode')]);
    }

    public function update(UpdateWorkflowRequest $request, Workflow $workflow): JsonResponse
    {
        $workflow->update($request->validated());

        return response()->json(['data' => $workflow->fresh()]);
    }

    public function destroy(Workflow $workflow): JsonResponse
    {
        Gate::authorize('delete', $workflow);
        $workflow->delete();

        return response()->json(status: 204);
    }
}
