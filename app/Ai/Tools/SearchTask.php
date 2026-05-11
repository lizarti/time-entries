<?php

namespace App\Ai\Tools;

use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchTask implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Use this tool to search for a task by name within a specific company. 
        This is useful for finding the correct task when the user provides a task name in their message.
        
        If the task is found, return the task name. If not found, return "Task not found."';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $companyId = $request->str('company_id');
        $taskName = $request->str('task_name');

        $task = Task::where('company_id', $companyId)
            ->where('name', $taskName)
            ->first();

        if (!$task) {
            return 'Task not found.';
        }

        return $task->id;
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'company_id' => $schema->integer()->required(),
            'task_name' => $schema->string()->required(),
        ];
    }
}
