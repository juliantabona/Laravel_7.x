<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class Users extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = 'App\Http\Resources\User';

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [

            '_links' => [
                
                //  Link to current resource
                'self' => [
                    'href' => url()->full(),
                    'title' => 'These users',
                ],

                'first' => [
                    'href' => $this->url(1),
                    'title' => 'First page of this collection',
                ],

                'prev' => [
                    'href' => $this->previousPageUrl(),
                    'title' => 'Previous page of this collection',
                ],

                'next' => [
                    'href' => $this->nextPageUrl(),
                    'title' => 'Next page of this collection',
                ],

                'last' => [
                    'href' => $this->url($this->lastPage()),
                    'title' => 'Last page of this collection',
                ],

                //  Link to search users
                'search' => [
                    'href' => url()->current().'?search={searchTerms}',
                    'templated' => true,
                ],
                
            ],

            '_embedded' => [
                'users' => $this->collection,
            ],

            'total' => $this->total(),
            'count' => $this->count(),
            'per_page' => $this->perPage(),
            'current_page' => $this->currentPage(),
            'total_pages' => $this->lastPage(),
            
        ];
    }

    /*
     *  This will remove all extra pagination fields from the JSON response (links, meta, etc) and allow you to 
     *  customize the response as you'd like in toArray($request). The toResponse method call is NOT static, 
     *  but instead calling the grandparent JsonResource::toResponse method, just as parent::toResponse would 
     *  call the ResourceCollection toResponse(..) instance method.
     *  Link: https://stackoverflow.com/questions/48094741/customising-laravel-5-5-api-resource-collection-pagination
     */
    public function toResponse($request)
    {
        return JsonResource::toResponse($request);
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