<?php

namespace App\Http\Controllers\Api;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    
    public function login(Request $request)
    {
        //  Validate the login inputs
        $loginData = $request->validate([
            'email' => 'email|required',
            'password' => 'required'
        ]);
        
        //  Attempt to login
        if( auth()->attempt($loginData) ){

            //  Create new token
            $accessToken = auth()->user()->createToken('authToken');

            //  Return response
            return response([
                'user' => auth()->user(),
                'access_token' => $accessToken
            ]);

        //  If attempt to login failed
        }else{

            //  Check if a user with the give email address exists
            if( \App\User::where('email', $loginData['email'])->exists() ){

                //  Since the email exists, this means that the password is incorrent. Throw a validation error
                throw ValidationException::withMessages(['password' => 'Your password is incorrect']);

            }else{

                //  The account with the given email does not exist. Throw a validation error
                throw ValidationException::withMessages(['email' => 'The account using the email "'.$loginData['email'].'" does not exist.']);

            }

        }
    }

    public function logout(Request $request)
    {

        //  Get the authenticated user
        $user = auth()->user();

        //  If we have a user
        if( $user ){

            //  Logout all devices
            if( $request->input('everyone') == 'true' || $request->input('everyone') == '1' ){
                
                //  This will log out all devices
                DB::table('oauth_access_tokens')->where('user_id', $user->id)->update([
                    'revoked' => true
                ]);

            //  Logout only current device
            }else{

                //  Get the user's token
                $token = $user->token();

                //  Revoke the token
                $token->revoke();
                
            }

        }

        //  Return nothing
        return response(null, 200);
    }

    public function register(Request $request)
    {
        //  Validate the registration inputs
        $registrationData = $request->validate([
            'name' => 'required|max:55',
            'email' => 'email|required|unique:users',
            'password' => 'required|confirmed'
        ]);
        
        //  Hash the password using bcrypt
        $registrationData['password'] = bcrypt($registrationData['password']);

        //  Create new user
        $user = \App\User::create($registrationData);

        //  Create new token
        $accessToken = $user->createToken('authToken');

        //  Return response
        return response([
            'user' => $user,
            'access_token' => $accessToken
        ]);
    }

}
