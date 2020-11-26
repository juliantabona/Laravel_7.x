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
            'type' => $this->type,
            'msisdn' => $this->msisdn,
            'request_type' => $this->request_type,
            'text' => $this->text,
            'reply_records' => $this->reply_records,
            'logs' => $this->logs,
            'test' => $this->test,
            'status' => $this->status,
            'fatal_error' => $this->fatal_error,
            'fatal_error_msg' => $this->fatal_error_msg,
            'allow_timeout' => $this->allow_timeout,
            'timeout_at' => $this->timeout_at,
            'estimated_record_sizes' => $this->estimated_record_sizes,
            'total_session_duration' => $this->total_session_duration,
            'user_response_durations' => $this->user_response_durations,
            'session_execution_times' => $this->session_execution_times,
    
            /*  Meta Data  */
            'metadata',

            /*  Timestamp Info  */
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            /*  Resource Links */
            '_links' => [

                'curies' => [
                    ['name' => 'sce', 'href' => 'https://oqcloud.co.bw/docs/rels/{rel}', 'templated' => true],
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
