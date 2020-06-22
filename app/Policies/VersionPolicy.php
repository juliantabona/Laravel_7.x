<?php

namespace App\Policies;

use App\User;
use App\Version;
use Illuminate\Auth\Access\HandlesAuthorization;

class VersionPolicy
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
     * Determine whether the user can view all versions.
     *
     * @param  \App\User $user
     * @param  \App\Version $model
     * @return mixed
     */
    public function viewAll(User $version)
    {
        //  Only the Super Admin can view all versions
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view the version.
     *
     * @param  \App\User $user
     * @param  \App\Version $version
     * @return mixed
     */
    public function view(User $user, Version $version)
    {
        //  Only an Admin or Editor can view this version
        return $version->project()->isAdmin($user->id)  ||
               $version->project()->isEditor($user->id);
    }

    /**
     * Determine whether the user can create versions.
     *
     * @param  \App\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        //  Any Authenticated user can create a versions
        return true;
    }

    /**
     * Determine whether the user can update the version.
     *
     * @param  \App\User $user
     * @param  \App\Version $version
     * @return mixed
     */
    public function update(User $user, Version $version)
    {
        //  Only an Admin, Editor can update this version
        return  $version->project()->isAdmin($user->id)  ||
                $version->project()->isEditor($user->id);
    }

    /**
     * Determine whether the user can delete the version.
     *
     * @param  \App\User $user
     * @param  \App\Version $version
     * @return mixed
     */
    public function delete(User $user, Version $version)
    {
        //  Only an Admin can delete this version
        return $version->project()->isAdmin($user->id);
    }

    /**
     * Determine whether the user can restore the version.
     *
     * @param  \App\User $user
     * @param  \App\Version $version
     * @return mixed
     */
    public function restore(User $user, Version $version)
    {
        //  Only an Admin can restore this version
        return $version->project()->isAdmin($user->id);
    }

    /**
     * Determine whether the user can permanently delete the version.
     *
     * @param  \App\User $user
     * @param  \App\Version $version
     * @return mixed
     */
    public function forceDelete(User $user, Version $version)
    {
        //  Only an Admin can force delete this version
        return $version->project()->isAdmin($user->id);
    }
}
