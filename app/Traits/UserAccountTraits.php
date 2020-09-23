<?php

namespace App\Traits;

//  Resources
use App\Http\Resources\UserAccount as UserAccountResource;
use App\Http\Resources\UserAccounts as UserAccountsResource;

trait UserAccountTraits
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
            return new UserAccountsResource($users);

        }else{
            
            //  Transform the single instance
            return new UserAccountResource($this);

        }
    }

}
