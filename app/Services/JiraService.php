<?php 

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JiraService
{
    public static function createIssue($summary, $description)
    {
        $response = Http::withBasicAuth(config('services.jira.user'), config('services.jira.token'))
            ->post("https://" . config('services.jira.domain') . "/rest/api/3/issue", [
                'fields' => [
                    'project' => ['key' => config('services.jira.project')],
                    'summary' => $summary,
                    'description' => [
                        'type' => 'doc',
                        'version' => 1,
                        'content' => [
                            [
                                'type' => 'paragraph', 
                                'content' => [
                                    ['type' => 'text', 'text' => $description . "\n\nRequester: " . \Filament\Facades\Filament::auth()->user()->email]
                                ]
                            ]
                        ],
                    ],
                    'issuetype' => ['name' => 'Request'],
                ],
            ]);

        /** @var \Illuminate\Http\Client\Response $response */

        if ($response->failed()) {
            // If it still fails, this will show the specific reason (like missing required fields)
            dd($response->json());
            Log::error('Jira API Error: ' . $response->body());
            return ['key' => 'ERROR-API'];
        }

        return $response->json();
    }

    public static function addCommentAndClose($ticketKey, $approverEmail, $rejectReason = "")
    {
        $domain = config('services.jira.domain');
        $auth = [config('services.jira.user'), config('services.jira.token')];
    
        // 1. Determine the message based on presence of a rejection reason
        if ($rejectReason) {
            $message = "This request was REJECTED by: {$approverEmail}. Reason: {$rejectReason}";
            $transitionId = '31'; // Usually 'In Progress' or 'Reopened' for rejections
        } else {
            $message = "This request was APPROVED by: {$approverEmail}.";
            $transitionId = '31'; // Usually 'Done' or 'Closed'
        }
    
        // 2. Add Comment (using Jira's required ADF format)
        Http::withBasicAuth(...$auth)
            ->post("https://{$domain}/rest/api/3/issue/{$ticketKey}/comment", [
                'body' => [
                    'type' => 'doc',
                    'version' => 1,
                    'content' => [
                        [
                            'type' => 'paragraph',
                            'content' => [
                                [
                                    'type' => 'text', 
                                    'text' => $message
                                ]
                            ]
                        ]
                    ]
                ]
            ]);
    
        // 3. Transition the issue
        // Note: Verify your Jira Transition IDs. '31' is common for Done, 
        // but you might want a different one for Rejections.
        Http::withBasicAuth(...$auth)
            ->post("https://{$domain}/rest/api/3/issue/{$ticketKey}/transitions", [
                'transition' => ['id' => $transitionId] 
            ]);
    }
}