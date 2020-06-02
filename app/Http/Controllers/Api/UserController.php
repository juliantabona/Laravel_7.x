<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getUser(Request $request)
    {
        //  Get the specified user's id or use the authenticated users id
        $user_id = $request->route('user_id') ?? auth('api')->user()->id;

        //  Get the user
        $user = \App\User::where('id', $user_id)->first() ?? null;

        //  Check if the user exists
        if ($user) {

            //  Check if the current auth user is authourized to view this user resource
            if ($user->can('view', $user)) {

                //  Return an API Readable Format of the User Instance
                return $user->convertToApiFormat();

            } else {

                //  Not Authourized
                return help_not_authorized();

            }
            
        } else {

            //  Not Found
            return help_resource_not_fonud();

        }
    }
    
    public function updateUser(Request $request, $id)
    {
        //
    }
    
    public function destroyUser($id)
    {
        //
    }
}
