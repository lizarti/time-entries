<?php

namespace App\Ai\Agents;

use App\Ai\Tools\SearchCompany;
use App\Ai\Tools\SearchEmployee;
use App\Ai\Tools\SearchProject;
use App\Ai\Tools\SearchTask;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Stringable;

#[Model('gpt-5.4-mini')]

class TimeTracker implements Agent, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return '
        You are a time tracking assistant. Your goal is to help the user log hours worked on tasks in a simple and single interaction. You will be provided with a user message describing the work done, and you must extract the relevant information to create a time entry.
        Do not make assumptions about missing data. 
        Only provide the structured output when you have all the required information.
        
        You will use tools to look up the real values for the company, employee, project, and task based on the names provided by the user. 
        Just return the name of the company, employee, project, and task as they appear in the database. Do not return IDs or any other information from the tools.

## Your behavior

- Interpret dates flexibly: "today", "yesterday", "last Monday", "the 5th" etc.
- Interpret hours flexibly: "3h", "half day", "three and a half hours", "1.5h" etc.

## Required data for a time entry

- the name of the company the user worked for
- the name of the employee who did the work
- the name of the project they worked on
- the name of the task they did
- the date they worked
- the number of hours they worked

## Expected flow

1. The user describes what they worked on (eg: John worked on Project X on 01/01/2026 doing cleanup for 4 hours).
2. First, you will need to extract the company name, and search for it using the SearchCompany tool to get the real company id from the database.
3. With the company id, you can then extract the employee name, project name, and task name, and search for them in the database to get the real values.
4. The agent returns the structured output with the real values.

Important: If any required information is missing or cannot be found, respond with "Unable to create time entry. Invalid or missing information for: [list invalid/missing fields].

Return entities in the following JSON format:
{
    "company_id": integer,
    "company_name": string,
    "employee_id": integer,
    "employee_name": string,
    "project_id": integer,
    "project_name": string,
    "task_id": integer,
    "task_name": string,
    "date": string (in Y-m-d format),
    "hours": number
}
';
    }
    
    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            new SearchCompany(),
            new SearchEmployee(),
            new SearchProject(),
            new SearchTask()
        ];
    }
    
    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'company_id' => $schema->integer()->required(),
            'company_name' => $schema->string()->required(),
            'employee_id' => $schema->integer()->required(),
            'employee_name' => $schema->string()->required(),
            'project_id' => $schema->integer()->required(),
            'project_name' => $schema->string()->required(),
            'task_id' => $schema->integer()->required(),
            'task_name' => $schema->string()->required(),
            'date' => $schema->string()->required(),
            'hours' => $schema->number()->required(),
        ];
    }
}
