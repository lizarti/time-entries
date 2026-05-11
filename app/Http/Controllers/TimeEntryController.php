<?php

namespace App\Http\Controllers;

use App\Actions\TimeEntry\BulkInsertTimeEntriesAction;
use App\Actions\TimeEntry\ListTimeEntriesAction;
use App\Actions\TimeEntry\SummarizeTimeEntriesAction;
use App\Actions\TimeEntry\UpdateTimeEntryAction;
use App\Ai\Agents\TimeTracker;
use App\Exceptions\ConflictException;
use App\Http\Requests\BulkInsertTimeEntriesRequest;
use App\Http\Requests\UpdateTimeEntryRequest;
use App\Http\Resources\TimeEntryResource;
use App\Models\TimeEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TimeEntryController extends Controller
{
    public function index(Request $request, ListTimeEntriesAction $action): AnonymousResourceCollection
    {
        $sortDir = in_array($request->input('sort_dir'), ['asc', 'desc'])
            ? $request->input('sort_dir')
            : 'desc';

        return TimeEntryResource::collection(
            $action->execute(
                filters: $request->only(['company_id', 'employee_id', 'project_id', 'task_id', 'date_from', 'date_to', 'search']),
                sortBy:  $request->input('sort_by', 'date'),
                sortDir: $sortDir,
                perPage: min((int) $request->input('per_page', 25), 100),
            )
        );
    }

    public function summary(Request $request, SummarizeTimeEntriesAction $action): JsonResponse
    {
        return response()->json([
            'data' => $action->execute(
                $request->only(['company_id', 'employee_id', 'project_id', 'task_id', 'date_from', 'date_to', 'search']),
            ),
        ]);
    }

    public function bulkStore(
        BulkInsertTimeEntriesRequest $request,
        BulkInsertTimeEntriesAction  $action,
    ): JsonResponse {
        $entries = $action->execute($request->input('entries'));

        return TimeEntryResource::collection($entries)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        UpdateTimeEntryRequest $request,
        UpdateTimeEntryAction  $action,
        TimeEntry              $timeEntry,
    ): JsonResponse {
        try {
            $entry = $action->execute($timeEntry, $request->validated());
        } catch (ConflictException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return (new TimeEntryResource($entry))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function parseUsingAI(Request $request): JsonResponse
    {
        $agent       = new TimeTracker();
        $userMessage = $request->input('message');

        if (empty($userMessage)) {
            return response()->json(['message' => 'Message is required.'], 400);
        }

        try {
            $agentResponse = $agent->prompt($userMessage);
            $jsonResponse  = json_decode($agentResponse->text);

            return response()->json($jsonResponse, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Unable to create time entry. Invalid or missing information.'], 422);
        }
    }
}
