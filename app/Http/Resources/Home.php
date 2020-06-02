<?php

namespace App\Http\Resources;

use App\Http\Resources\User as UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class Home extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [

            /*  Resource Links */
            '_links' => [
                
                'curies' => [
                    [ 'name' => 'sce', 'href' => 'https://oq-sce.co.bw/docs/rels/{rel}', 'templated' => true ]
                ],

                //  Link to current resource
                'self' => [ 
                    'href' => url()->full(),
                    'title' => 'API Home - Your API starting point.'
                ],

                //  Link to current resource
                'sce:login' => [ 
                    'href' => route('login'),
                    'title' => 'Authenticate user'
                ],

                //  Link to current resource
                'sce:register' => [ 
                    'href' => route('register'),
                    'title' => 'Register new user'
                ],

                //  Link to current resource
                'sce:logout' => [ 
                    'href' => route('logout'),
                    'title' => 'Logout current device'
                ],

                //  Link to current resource
                'sce:logout-everyone' => [ 
                    'href' => route('logout', ['everyone' => 'true']),
                    'title' => 'Logout all devices'
                ]
                
            ],

            /*  Embedded Resources */
            '_embedded' => [
                
                //  Me Resource
                'me' => ($user = auth('api')->user()) ? (new UserResource($user)) : null
                
            ]

        ];
    }

    /**
     * Customize the outgoing response for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return void
     */
    public function withResponse($request, $response)
    {
        $response->header('Content-Type', 'application/hal+json');
    }

}