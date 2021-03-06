<?php

namespace App\Http\Resources;

use App\Http\Resources\Version as VersionResource;
use App\Http\Resources\ShortCode as ShortCodeResource;
use Illuminate\Http\Resources\Json\JsonResource;

class Project extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'hex_color' => $this->hex_color,
            'online' => $this->online,
            'offline_message' => $this->offline_message,
            'active_version_id' => $this->active_version_id,

            /*  Timestamp Info  */
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            /*  Resource Links */
            '_links' => [

                'curies' => [
                    ['name' => 'sce', 'href' => 'https://oqcloud.co.bw/docs/rels/{rel}', 'templated' => true],
                ],

                //  Link to current resource
                'self' => [
                    'href' => route('project', ['project_id' => $this->id]),
                    'title' => 'This project',
                ],

                //  Link to the project versions
                'sce:versions' => [
                    'href' => route('project-versions', ['project_id' => $this->id]),
                    'title' => 'The versions that belong to this project',
                    'total' => $this->versions()->count()
                ],

                //  Link to the project sessions
                'sce:sessions' => [
                    'href' => route('project-sessions', ['project_id' => $this->id]),
                    'title' => 'The sessions that belong to this project'
                ],

                //  Link to the project live sessions
                'sce:live_sessions' => [
                    'href' => route('project-live-sessions', ['project_id' => $this->id]),
                    'title' => 'The live sessions that belong to this project'
                ],

                //  Link to the project test sessions
                'sce:test_sessions' => [
                    'href' => route('project-test-sessions', ['project_id' => $this->id]),
                    'title' => 'The test sessions that belong to this project'
                ],

                //  Link to the project analytics
                'sce:analytics' => [
                    'href' => route('project-analytics', ['project_id' => $this->id]),
                    'title' => 'The analytics that belong to this project'
                ],

                //  Link to the project live analytics
                'sce:live_analytics' => [
                    'href' => route('project-live-analytics', ['project_id' => $this->id]),
                    'title' => 'The live analytics that belong to this project'
                ],

                //  Link to the project test analytics
                'sce:test_analytics' => [
                    'href' => route('project-test-analytics', ['project_id' => $this->id]),
                    'title' => 'The test analytics that belong to this project'
                ],
                //  Link to the project user accounts
                'sce:user-accounts' => [
                    'href' => route('user-accounts', ['project_id' => $this->id]),
                    'title' => 'The user accounts that belong to this project',
                ],

                //  Link to the project test user accounts
                'sce:test-user-accounts' => [
                    'href' => route('test-user-accounts', ['project_id' => $this->id]),
                    'title' => 'The test user accounts that belong to this project',
                ],

                //  Link to the ussd service builder
                'sce:ussd_service_builder' => [
                    'href' => route('ussd-service-builder'),
                    'title' => 'The ussd service builder',
                ]

            ],

            /*  Embedded Resources */
            '_embedded' => [

                //  Short Code Resource
                'short_code' => $this->shortCode ? (new ShortCodeResource($this->shortCode)) : null,

                //  Active Version Resource
                'active_version' => $this->activeVersion ? (new VersionResource($this->activeVersion)) : null

            ],
        ];
    }

    /**
     * Customize the outgoing response for the resource.
     *
     * @param \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response $response
     */
    public function withResponse($request, $response)
    {
        $response->header('Content-Type', 'application/hal+json');
    }
}
