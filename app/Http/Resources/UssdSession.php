<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UssdSession extends JsonResource
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
            'session_id' => $this->session_id,
            'service_code' => $this->service_code,
            'msisdn' => $this->msisdn,
            'request_type' => $this->request_type,
            'text' => $this->text,
            'status' => $this->status,
            'allow_timeout' => $this->allow_timeout,
            'timeout_at' => $this->timeout_at,
            'metadata' => $this->metadata,

            /*  Timestamp Info  */
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            /*  Resource Links */
            '_links' => [

                'curies' => [
                    ['name' => 'oq', 'href' => 'https://oqcloud.co.bw/docs/rels/{rel}', 'templated' => true],
                ]
                
            ]

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
