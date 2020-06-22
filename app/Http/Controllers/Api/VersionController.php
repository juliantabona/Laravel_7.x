<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class VersionController extends Controller
{
    private $user;

    public function __construct(Request $request)
    {
        //  Get the authenticated user
        $this->user = auth('api')->user();
    }

    public function getVersion($version_id)
    {
        //  Get the version
        $version = \App\Version::where('id', $version_id)->first() ?? null;

        //  Check if the version exists
        if ($version) {

            //  Check if the user is authourized to view the version
            if ($this->user->can('view', $version)) {

                //  Return an API Readable Format of the Version Instance
                return $version->convertToApiFormat();

            } else {

                //  Not Authourized
                return help_not_authorized();

            }
            
        } else {

            //  Not Found
            return help_resource_not_fonud();

        }
    }

}
