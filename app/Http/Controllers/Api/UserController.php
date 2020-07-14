<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    private $user;

    public function __construct(Request $request)
    {
        //  Get the specified user's id or use the authenticated users id
        $user_id = $request->route('user_id') ?? auth('api')->user()->id;

        //  Get the user
        $this->user = \App\User::where('id', $user_id)->first() ?? null;

        //  Check if the user exists
        if ( !$this->user ) {

            //  Not Found
            return help_resource_not_fonud();

        }
    }
    
    public function getUser(Request $request)
    {
        //  Check if the current auth user is authourized to view this user resource
        if ($this->user->can('view', $this->user)) {

            //  Return an API Readable Format of the User Instance
            return $this->user->convertToApiFormat();

        } else {

            //  Not Authourized
            return help_not_authorized();

        }
    }

    public function getUserProjects(Request $request)
    {
        //  Get the user projects
        $projects = $this->user->projects()->latest()->paginate() ?? null;

        //  Check if the user projects exist
        if ($projects) {

            //  Check if the current auth user is authourized to view this user projects resource
            if ($this->user->can('view', $this->user)) {
                
                //  Return an API Readable Format of the Project Instance
                return ( new \App\Project() )->convertToApiFormat($projects);

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
