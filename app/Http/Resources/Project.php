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
            'online' => $this->online,
            'offline_message' => $this->offline_message,
            'active_version_id' => $this->active_version_id,
            

            /*  Timestamp Info  */
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            /*  Resource Links */
            '_links' => [

                'curies' => [
                    ['name' => 'oq', 'href' => 'https://oqcloud.co.bw/docs/rels/{rel}', 'templated' => true],
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

                //  Link to the ussd service builder
                'sce:ussd_service_builder' => [
                    'href' => route('ussd-service-builder'),
                    'title' => 'The ussd service builder',
                ],

                //  Link to the payment methods
                'sce:payment_methods' => [
                    'href' => route('payment-methods'),
                    'title' => 'The payment methods used for billing'
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
