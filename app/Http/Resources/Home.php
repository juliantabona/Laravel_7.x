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

                //  Link to login
                'sce:login' => [ 
                    'href' => route('login'),
                    'title' => 'Authenticate user'
                ],

                //  Link to register
                'sce:register' => [ 
                    'href' => route('register'),
                    'title' => 'Register new user'
                ],

                //  Link to send password reset link
                'sce:send-password-reset-link' => [ 
                    'href' => route('send-password-reset-link'),
                    'title' => 'Send the password reset link'
                ],

                //  Link to send password reset link
                'sce:reset-password' => [ 
                    'href' => route('reset-password'),
                    'title' => 'Reset the user\'s password'
                ],

                //  Link to logout from current device
                'sce:logout' => [ 
                    'href' => route('logout'),
                    'title' => 'Logout from current device'
                ],

                //  Link to logout from all devices
                'sce:logout-everyone' => [ 
                    'href' => route('logout', ['everyone' => 'true']),
                    'title' => 'Logout all devices'
                ],

                //  Link to projects resources (Used to create new project resource)
                'sce:projects' => [
                    'href' => route('project-create'),
                    'title' => 'Get or create projects'
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