<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Project;
use Illuminate\Http\Request;

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
        $project = \App\Project::where('id', $project_id)->with('activeVersion:id,number,description,project_id')->first() ?? null;

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

    public function createProject(Request $request)
    {
        //  Check if the user is authourized to update the create
        if ($this->user->can('create', Project::class)) {
            //  Update the project
            $project = (new Project())->initiateCreate($request);

            //  If the created successfully
            if ($project) {
                //  Return an API Readable Format of the Project Instance
                return $project->convertToApiFormat();
            }
        } else {
            //  Not Authourized
            return help_not_authorized();
        }
    }

    public function updateProject(Request $request, $project_id)
    {
        //  Get the project
        $project = \App\Project::where('id', $project_id)->first() ?? null;

        //  Check if the project exists
        if ($project) {
            //  Check if the user is authourized to update the project
            if ($this->user->can('update', $project)) {
                //  Update the project
                $updated = $project->update($request->all());

                //  If the update was successful
                if ($updated) {
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

    public function getProjectSessions(Request $request, $project_id)
    {
        //  Get the project
        $project = \App\Project::where('id', $project_id)->first() ?? null;

        //  Get the session type e.g live or test
        $type = $request->input('type');

        if ($type == 'live') {
            //  Get the project live sessions
            $sessions = $project->liveSessions()->paginate() ?? null;
        } elseif ($type == 'test') {
            //  Get the project test sessions
            $sessions = $project->testSessions()->paginate() ?? null;
        } else {
            //  Get the project sessions
            $sessions = $project->sessions()->paginate() ?? null;
        }

        //  Check if the project sessions exist
        if ($sessions) {
            //  Check if the current auth user is authourized to view the project sessions resource
            if ($this->user->can('view', $project)) {
                //  Return an API Readable Format of the Session Instance
                return ( new \App\UssdSession() )->convertToApiFormat($sessions);
            } else {
                //  Not Authourized
                return help_not_authorized();
            }
        } else {
            //  Not Found
            return help_resource_not_fonud();
        }
    }

    public function getProjectAnalytics(Request $request, $project_id)
    {
        //  Get the project
        $project = \App\Project::where('id', $project_id)->first() ?? null;

        //  Check if the project exist
        if ($project) {
            //  Check if the current auth user is authourized to view the project analytics resource
            if ($this->user->can('view', $project)) {
                //  Get the session type e.g live or test
                $type = $request->input('type');

                if ($type == 'live') {
                    //  Get the project live analytics
                    $analytics = $project->getLiveAnalytics();
                } elseif ($type == 'test') {
                    //  Get the project test analytics
                    $analytics = $project->getTestAnalytics();
                } else {
                    //  Get the project analytics
                    $analytics = $project->getAnalytics();
                }

                //  Return analytics
                return response()->json(['analytics' => $analytics], 200);
            } else {
                //  Not Authourized
                return help_not_authorized();
            }
        } else {
            //  Not Found
            return help_resource_not_fonud();
        }
    }

    public function getProjectUserAccounts(Request $request, $project_id)
    {
        //  Get the project
        $project = \App\Project::where('id', $project_id)->first() ?? null;

        //  If we should get fake user accounts
        if ($request->input('test') == 'true') {
            //  Get the project fake user accounts
            $user_accounts = $project->fakeUserAccounts()->paginate() ?? null;
        } else {
            //  Get the project user accounts
            $user_accounts = $project->userAccounts()->paginate() ?? null;
        }

        //  Check if the project user accounts exist
        if ($user_accounts) {
            //  Check if the current auth user is authourized to view the project user accounts resource
            if ($this->user->can('view', $project)) {
                //  Return an API Readable Format of the UserAccount Instance
                return ( new \App\UserAccount() )->convertToApiFormat($user_accounts);
            } else {
                //  Not Authourized
                return help_not_authorized();
            }
        } else {
            //  Not Found
            return help_resource_not_fonud();
        }
    }

    public function deleteProject(Request $request, $project_id)
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
