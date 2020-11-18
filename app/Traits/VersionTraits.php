<?php

namespace App\Traits;

use App\Http\Resources\Version as VersionResource;
use App\Http\Resources\Versions as VersionsResource;

trait VersionTraits
{
    private $request;
    private $version;

    /*  convertToApiFormat() method:
     *
     *  Converts to the appropriate Api Response Format
     *
     */
    public function convertToApiFormat($versions = null)
    {
        if ($versions) {
            //  Transform the multiple instances
            return new VersionsResource($versions);
        } else {
            //  Transform the single instance
            return new VersionResource($this);
        }
    }

    public function getBuilderTemplate()
    {
        return [
            'screens' => [],
            'markers' => [],
            'global_events' => [],
            'global_variables' => [],
            'subscription_plans' => [],
            'conditional_screens' => [
                'active' => false,
                'code' => null,
            ],
            'simulator' => [
                'debugger' => [
                    'return_logs' => true,
                    'return_log_types' => [
                        'info', 'warning', 'error',
                    ],
                ],
                'subscriber' => [
                        'phone_number' => '2677747908',
                    ],
                'settings' => [
                    'allow_timeouts' => true,
                    'timeout_limit_in_seconds' => 120,
                    'timeout_message' => 'TIMEOUT: You have exceeded your time limit.',
                ],
            ],
         ];
    }

    /*  This method creates a new Version
     */
    public function initiateCreate($request)
    {
        $this->request = $request;

        //  Set the template
        $template = [
            'project_id' => $this->request->input('project_id'),
            'number' => $this->request->input('number') ?? 1.00,
            'description' => $this->request->input('description') ?? null,
            'builder' => $this->request->input('builder') ?? $this->getBuilderTemplate(),
        ];

        try {
            /*
             *  Create new a version, then retrieve a fresh instance
             */
            $this->version = $this->create($template);

            //  If the version was created successfully
            if ($this->version) {
                return $this->version->fresh();
            }
        } catch (\Exception $e) {
            //  Throw a validation error
            throw $e;
        }
    }
}
