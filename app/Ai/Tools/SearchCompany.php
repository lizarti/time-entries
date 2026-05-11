<?php

namespace App\Ai\Tools;

use App\Models\Company;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchCompany implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 
        'Use this tool to search for a company by name. 
        This is useful for finding the correct company when the user provides a company name in their message.
        
        If the company is found, return the company name. If not found, return "Company not found."';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $companyName = $request->str('company_name');

        $company = Company::where('name', $companyName)->first();

        if (!$company) {
            return 'Company not found.';
        }

        return $company->id;
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'company_name' => $schema->string()->required(),
        ];
    }
}
