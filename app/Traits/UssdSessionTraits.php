<?php

namespace App\Traits;

//  Resources
use App\Http\Resources\UssdSession as UssdSessionResource;
use App\Http\Resources\UssdSessions as UssdSessionsResource;

trait UssdSessionTraits
{
    public $default_timeout_message = 'TIMEOUT: You have exceeded your session time limit';

    /*  convertToApiFormat() method:
     *
     *  Converts to the appropriate Api Response Format
     *
     */
    public function convertToApiFormat($ussd_sessions = null)
    {
        if( $ussd_sessions ){
                
            //  Transform the ussd sessions
            return new UssdSessionsResource($ussd_sessions);

        }else{
            
            //  Transform the ussd session
            return new UssdSessionResource($this);

        }
    }
}
