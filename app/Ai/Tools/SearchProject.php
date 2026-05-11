<?php

namespace App\Ai\Tools;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchProject implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Use this tool to search for a project by name within a specific company. 
        This is useful for finding the correct project when the user provides a project name in their message.
        
        If the project is found, return the project name. If not found, return "Project not found."';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $companyId = $request->str('company_id');
        $projectName = $request->str('project_name');

        $project = Project::where('company_id', $companyId)
            ->where('name', $projectName)
            ->first();

        if (!$project) {
            return 'Project not found.';
        }
    
        return $project->id;
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'company_id' => $schema->integer()->required(),
            'project_name' => $schema->string()->required(),
        ];
    }
}
