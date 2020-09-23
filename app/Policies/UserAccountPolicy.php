<?php

namespace App\Policies;

use App\User;
use App\UserAccount;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserAccountPolicy
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
     * Determine whether the user can view all user accounts.
     *
     * @param  \App\User $user
     * @param  \App\UserAccount $model
     * @return mixed
     */
    public function viewAll(User $user)
    {
        //  Only the Super Admin can view all user accounts
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view the user account.
     *
     * @param  \App\User $user
     * @param  \App\UserAccount $user_account
     * @return mixed
     */
    public function view(User $user, UserAccount $user_account)
    {
        //  Only an Admin or Editor can view this user account
        return $user_account->project()->isAdmin($user->id)  ||
               $user_account->project()->isEditor($user->id);
    }

    /**
     * Determine whether the user can create user accounts.
     *
     * @param  \App\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        //  Any Authenticated user can create a user accounts
        return true;
    }

    /**
     * Determine whether the user can update the user account
     *
     * @param  \App\User $user
     * @param  \App\UserAccount $user_account
     * @return mixed
     */
    public function update(User $user, UserAccount $user_account)
    {
        //  Only an Admin, Editor can update this user account
        return  $user_account->project()->isAdmin($user->id)  ||
                $user_account->project()->isEditor($user->id);
    }

    /**
     * Determine whether the user can delete the user account
     *
     * @param  \App\User $user
     * @param  \App\UserAccount $user_account
     * @return mixed
     */
    public function delete(User $user, UserAccount $user_account)
    {
        //  Only an Admin can delete this user account
        return $user_account->project()->isAdmin($user->id);
    }

    /**
     * Determine whether the user can restore the user account
     *
     * @param  \App\User $user
     * @param  \App\UserAccount $user_account
     * @return mixed
     */
    public function restore(User $user, UserAccount $user_account)
    {
        //  Only an Admin can restore this user account
        return $user_account->project()->isAdmin($user->id);
    }

    /**
     * Determine whether the user can permanently delete the user account
     *
     * @param  \App\User $user
     * @param  \App\UserAccount $user_account
     * @return mixed
     */
    public function forceDelete(User $user, UserAccount $user_account)
    {
        //  Only an Admin can force delete this user account
        return $user_account->project()->isAdmin($user->id);
    }
}
