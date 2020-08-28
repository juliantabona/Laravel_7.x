<?php

namespace App\Traits;

use Illuminate\Validation\ValidationException;
use App\Http\Resources\Project as ProjectResource;
use App\Http\Resources\Projects as ProjectsResource;

trait ProjectTraits
{
    public $project;
    public $project_to_clone = null;
    public $default_offline_offline_message = 'Sorry, we are currently offline';

    /*  convertToApiFormat() method:
     *
     *  Converts to the appropriate Api Response Format
     *
     */
    public function convertToApiFormat($projects = null)
    {
        if( $projects ){
                
            //  Transform the multiple instances
            return new ProjectsResource($projects);

        }else{
            
            //  Transform the single instance
            return new ProjectResource($this);

        }
    }

    /*  This method creates a new project
     */
    public function initiateCreate( $request )
    {   
        //  Validate the request
        $validation_data = $request->validate([
            'name' => 'required'
        ]);

        //  If we have the project id representing the project to clone
        if ( $request->input('clone_project_id') ) {

            //  Retrieve the project to clone
            $this->project_to_clone = \App\Project::where('id', $request->input('clone_project_id') )->with('versions')->first();

        }
        
        //  Set the template
        $template = [
            'online' => true,
            'active_version_id' => null,
            'user_id' => auth()->user()->id,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'offline_message' => $this->default_offline_offline_message
        ];

        try {
            
            /*
             *  Create new a project, then retrieve a fresh instance
             */
            $this->project = $this->create($template)->fresh();

            //  If created successfully
            if ($this->project) {

                $this->assignUserAsAdmin();

                // Create and assign a short code
                $this->createAndAssignShortCode($request);

                // Create and assign a version
                $this->createAndAssignVersion($request);

                //  Return a fresh instance
                return $this->project->fresh();

            }

        } catch (\Exception $e) {

            //  Throw a validation error
            throw ValidationException::withMessages(['general' => $e->getMessage()]);
            
        }
    }

    public function assignUserAsAdmin()
    {
        //  Associate the project with the current user as admin
        auth()->user()->projects()->save($this->project,
        //  Pivot table values
        [
            'type' => 'admin',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function createAndAssignShortCode($request)
    {
        //  Set the project id on the request
        $request->merge(['project_id' => $this->project->id]);

        //  Create a new shortcode
        $shortcode = ( new \App\ShortCode() )->initiateCreate( $request );
    }

    public function createAndAssignVersion($request)
    {
        //  Set the project id on the request
        $request->merge(['project_id' => $this->project->id]);

        $active_version_id = null;

        //  If we have version to clone
        if( $this->project_to_clone ){

            //  Foreach version
            foreach ($this->project_to_clone->versions as $version_to_clone) {

                //  Retrieve the version details
                $request->merge(['user_id' => auth()->user()->id]);
                $request->merge(['number' => $version_to_clone->number]);
                $request->merge(['builder' => $version_to_clone->builder]);
                $request->merge(['description' => $version_to_clone->description]);

                //  Clone the version
                $version = ( new \App\Version() )->initiateCreate( $request );

                //  If the version was cloned
                if( $version ){

                    //  Set the active version
                    if( $this->project_to_clone->active_version_id == $version_to_clone->id ){

                        $this->project->update([
                            'active_version_id' => $version->id
                        ]);

                    }
                }

            }

        }else{

            //  Create a new version
            $version = ( new \App\Version() )->initiateCreate( $request );

            //  If the version was cloned
            if( $version ){

                $this->project->update([
                    'active_version_id' => $version->id
                ]);

            }
        }
    }

    /*
     *  Checks if a given user is the owner of the project
     */
    public function isOwner($user_id)
    {
        return $this->whereUserId($user_id)->exists();
    }

    /*
     *  Checks if a given user is the admin of the project
     * 
     *  Technical administrators have complete control over the project. They can appoint new users 
     *  and change existing user roles, except Owner roles. They create, edit and delete existing 
     *  projects. They can view, and manage "Billing" and "Subscription" details of the project
     *  actions. Basically they manage everything.
     */
    public function isAdmin($user_id = null)
    {
        if($user_id) return $this->users()->wherePivot('user_id', $user_id)->wherePivot('type', 'admin')->exists();
    }

    /*
     *  Checks if a given user is the editor of the project
     * 
     *  Technical editors have control to edit the project. They cannot appoint new users 
     *  or change existing user roles. They also cannot delete the project or view any of
     *  the project "Billing" and "Subscription" details. Basically they only build and
     *  manage the technical side of the project.
     */
    public function isEditor($user_id = null)
    {
        if($user_id) return $this->users()->wherePivot('user_id', $user_id)->wherePivot('type', 'editor')->exists();
    }

    /*
     *  Checks if a given user is the viewer of the project
     * 
     *  Ideal for finance and accounting staff, this role gives complete access to "Billing"
     *  and "Subscription" details of the project as well as the ability to make necessary
     *  actions. Basically they only manage the business side of the project.
     */
    public function isBiller($user_id = null)
    {
        if($user_id) return $this->users()->wherePivot('user_id', $user_id)->wherePivot('type', 'biller')->exists();
    }

    /*
     *  Checks if a given user is the viewer of the project
     * 
     *  Viewers can only view "Billing" and "Subscription" details of the project.
     *  They cannot edit anything at all. Basically they only oversee the business 
     *  side of the project.
     */
    public function isViewer($user_id = null)
    {
        if($user_id) return $this->users()->wherePivot('user_id', $user_id)->wherePivot('type', 'viewer')->exists();
    }

}
