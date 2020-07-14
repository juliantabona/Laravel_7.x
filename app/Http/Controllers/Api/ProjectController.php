<?php

namespace App\Http\Controllers\Api;

use App\Project;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProjectController extends Controller
{
    private $user;

    public function __construct(Request $request)
    {
        //  Get the authenticated user
        $this->user = auth('api')->user();
    }

    public function getProject($project_id)
    {
        //  Get the project
        $project = Project::where('id', $project_id)->first() ?? null;

        //  Check if the project exists
        if ($project) {

            //  Check if the user is authourized to view the project
            if ($this->user->can('view', $project)) {

                //  Return an API Readable Format of the Project Instance
                return $project->convertToApiFormat();

            } else {

                //  Not Authourized
                return help_not_authorized();

            }
            
        } else {

            //  Not Found
            return help_resource_not_fonud();

        }
    }

    public function createProject( Request $request )
    {
        //  Check if the user is authourized to update the create
        if ($this->user->can('create', Project::class)) {

            //  Update the project
            $project = (new Project)->initiateCreate( $request );

            //  If the created successfully
            if( $project ){

                //  Return an API Readable Format of the Project Instance
                return $project->convertToApiFormat();

            }

        } else {

            //  Not Authourized
            return help_not_authorized();

        }
    }

    public function updateProject( Request $request, $project_id )
    {
        //  Get the project
        $project = \App\Project::where('id', $project_id)->first() ?? null;

        //  Check if the project exists
        if ($project) {

            //  Check if the user is authourized to update the project
            if ($this->user->can('update', $project)) {

                //  Update the project
                $updated = $project->update( $request->all() );

                //  If the update was successful
                if( $updated ){

                    //  Return an API Readable Format of the Project Instance
                    return $project->fresh()->convertToApiFormat();

                }

            } else {

                //  Not Authourized
                return help_not_authorized();

            }
            
        } else {

            //  Not Found
            return help_resource_not_fonud();

        }
    }

    public function getProjectVersions($project_id)
    {
        //  Get the project
        $project = \App\Project::where('id', $project_id)->first() ?? null;

        //  Get the project versions
        $versions = $project->versions()->paginate() ?? null;

        //  Check if the project versions exist
        if ($versions) {

            //  Check if the current auth user is authourized to view the project versions resource
            if ($this->user->can('view', $project)) {
                
                //  Return an API Readable Format of the Version Instance
                return ( new \App\Version() )->convertToApiFormat($versions);

            } else {

                //  Not Authourized
                return help_not_authorized();

            }
            
        } else {

            //  Not Found
            return help_resource_not_fonud();

        }
    }

    public function deleteProject( Request $request, $project_id )
    {
        //  Get the project
        $project = \App\Project::where('id', $project_id)->first() ?? null;

        //  Check if the project exists
        if ($project) {

            //  Check if the user is authourized to permanently delete the project
            if ($this->user->can('forceDelete', $project)) {

                //  Delete the project
                $project->delete();

                //  Return nothing
                return response()->json(null, 200);

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
