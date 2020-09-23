<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserAccountController extends Controller
{
    private $user;

    public function __construct(Request $request)
    {
        //  Get the authenticated user
        $this->user = auth('api')->user();
    }

    public function getUserAccount($user_account_id)
    {
        //  Get the user account
        $user_account = \App\UserAccount::where('id', $user_account_id)->first() ?? null;

        //  Check if the user account exists
        if ($user_account) {

            //  Check if the user is authourized to view the user account
            if ($this->user->can('view', $user_account)) {

                //  Return an API Readable Format of the UserAccount Instance
                return $user_account->convertToApiFormat();

            } else {

                //  Not Authourized
                return help_not_authorized();

            }
            
        } else {

            //  Not Found
            return help_resource_not_fonud();

        }
    }

    public function updateUserAccount( Request $request, $user_account_id )
    {
        //  Get the user account
        $user_account = \App\UserAccount::where('id', $user_account_id)->first() ?? null;

        //  Check if the user account exists
        if ($user_account) {

            //  Check if the user is authourized to update the user account
            if ($this->user->can('update', $user_account)) {

                //  Update the user account
                $updated = $user_account->update( $request->all() );

                //  If the update was successful
                if( $updated ){

                    //  Return an API Readable Format of the UserAccount Instance
                    return $user_account->fresh()->convertToApiFormat();

                }

            } else {

                //  Not Authourized
                return oq_api_not_authorized();
            }

        }else{
            
            //  Not Found
            return oq_api_notify_no_resource();

        }
    }
}
