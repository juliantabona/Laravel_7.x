<?php

namespace App\Traits;

//  Resources
use App\Http\Resources\Project as ProjectResource;
use App\Http\Resources\Projects as ProjectsResource;

trait ProjectTraits
{
    /*  convertToApiFormat() method:
     *
     *  Converts to the appropriate Api Response Format
     *
     */
    public function convertToApiFormat($projects = null)
    {
        if( $projects ){
                
            //  Transform the projects
            return new ProjectsResource($projects);

        }else{
            
            //  Transform the company
            return new ProjectResource($this);

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
