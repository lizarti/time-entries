<?php

namespace App\Http\Controllers;

use App\Actions\TimeEntry\BulkInsertTimeEntriesAction;
use App\Http\Requests\BulkInsertTimeEntriesRequest;
use App\Http\Resources\TimeEntryResource;
use App\Models\TimeEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TimeEntryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return TimeEntryResource::collection(TimeEntry::all());
    }

    public function bulkStore(
        BulkInsertTimeEntriesRequest $request,
        BulkInsertTimeEntriesAction $action,
    ): JsonResponse {
        $entries = $action->execute($request->input('entries'));

        return TimeEntryResource::collection($entries)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
