<?php

namespace App\Policies;

use App\User;
use App\Project;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectPolicy
{
    use HandlesAuthorization;

    /**
     * Authorize any action on this given policy if the user
     * is a super admin.
     */
    public function before($user, $ability)
    {
        /** Note that this will run before any other checks. This means is we return true we will be authorized
         *  for every action. However be aware that if we return false here, then we are also not authorizing 
         *  all other methods. We must be careful here, we only return true if the user is a "Super Admin" 
         *  but nothing is they are not, since we want other methods to run their own local checks. 
         * 
        */
        if($user->isSuperAdmin()) return true;
    }
    
    /**
     * Determine whether the user can view all projects.
     *
     * @param  \App\User $user
     * @param  \App\Project $model
     * @return mixed
     */
    public function viewAll(User $project)
    {
        //  Only the Super Admin can view all projects
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view the project.
     *
     * @param  \App\User $user
     * @param  \App\Project $project
     * @return mixed
     */
    public function view(User $user, Project $project)
    {
        //  Only an Admin, Editor, Biller or Viewer can view this project
        return $project->isAdmin($user->id)  ||
               $project->isEditor($user->id) ||
               $project->isBiller($user->id) ||
               $project->isViewer($user->id);
    }

    /**
     * Determine whether the user can create projects.
     *
     * @param  \App\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        //  Any Authenticated user can create a projects
        return true;
    }

    /**
     * Determine whether the user can update the project.
     *
     * @param  \App\User $user
     * @param  \App\Project $project
     * @return mixed
     */
    public function update(User $user, Project $project)
    {
        //  Only an Admin, Editor or Biller can update this project
        return  $project->isAdmin($user->id)  ||
                $project->isEditor($user->id) ||
                $project->isBiller($user->id);
    }

    /**
     * Determine whether the user can delete the project.
     *
     * @param  \App\User $user
     * @param  \App\Project $project
     * @return mixed
     */
    public function delete(User $user, Project $project)
    {
        //  Only an Admin can delete this project
        return $project->isAdmin($user->id);
    }

    /**
     * Determine whether the user can restore the project.
     *
     * @param  \App\User $user
     * @param  \App\Project $project
     * @return mixed
     */
    public function restore(User $user, Project $project)
    {
        //  Only an Admin can restore this project
        return $project->isAdmin($user->id);
    }

    /**
     * Determine whether the user can permanently delete the project.
     *
     * @param  \App\User $user
     * @param  \App\Project $project
     * @return mixed
     */
    public function forceDelete(User $user, Project $project)
    {
        //  Only an Admin can force delete this project
        return $project->isAdmin($user->id);
    }
}
