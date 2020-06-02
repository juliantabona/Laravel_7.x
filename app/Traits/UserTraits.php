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
                
            //  Transform the users
            return new UsersResource($users);

        }else{
            
            //  Transform the company
            return new UserResource($this);

        }
    }

}
