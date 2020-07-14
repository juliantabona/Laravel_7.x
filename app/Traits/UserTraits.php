<?php

namespace App\Traits;

//  Resources
use App\Http\Resources\User as UserResource;
use App\Http\Resources\Users as UsersResource;

trait UserTraits
{
    /*  convertToApiFormat() method:
     *
     *  Converts to the appropriate Api Response Format
     *
     */
    public function convertToApiFormat($users = null)
    {
        if( $users ){
                
            //  Transform the multiple instances
            return new UsersResource($users);

        }else{
            
            //  Transform the single instance
            return new UserResource($this);

        }
    }

}
