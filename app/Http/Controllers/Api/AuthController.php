<?php

namespace App\Http\Controllers\Api;

use DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\SendPasswordResetLink;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
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

        //  Get the user's email
        $email = $loginData['email'];
        
        //  Attempt to login
        if( auth()->attempt($loginData) ){

            //  Create new access token
            $accessToken = auth()->user()->createToken('authToken');

            //  Return response
            return response([
                'user' => auth()->user(),
                'access_token' => $accessToken
            ]);

        //  If attempt to login failed
        }else{

            //  Check if a user with the given email address exists
            if( \App\User::where('email', $email)->exists() ){

                //  Since the email exists, this means that the password is incorrent. Throw a validation error
                throw ValidationException::withMessages(['password' => 'Your password is incorrect']);

            }else{

                //  The account with the given email does not exist. Throw a validation error
                throw ValidationException::withMessages(['email' => 'The account using the email "'.$email.'" does not exist.']);

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

        //  Create new access token
        $accessToken = $user->createToken('authToken');

        //  Return response
        return response([
            'user' => $user,
            'access_token' => $accessToken
        ]);
    }

    public function sendPasswordResetLink(Request $request)
    {
        /** Validate the user input
         *  
         *  password_reset_url: The client url used to attach the password reset token
         *  email: The users account email
         */
        $userData = $request->validate([
            'password_reset_url' => 'url|required',
            'email' => 'email|required',
        ]);

        //  Get the users email
        $email = $userData['email'];

        //  Get the password reset url
        $password_reset_url = $userData['password_reset_url'];
        
        //  Check if a user with the given email address exists
        if( \App\User::where('email', $email)->exists() ){

            //  Get the user
            $user = \App\User::where('email', $email)->first();

            //  Delete any old password reset tokens
            DB::table('password_resets')->where('email', $email)->delete();

            //  Create a new password reset token
            DB::table('password_resets')->insert([
                'email' => $email,
                'token' => Str::random(60)
            ]);

            //  Get the new password reset data
            $password_reset = DB::table('password_resets')->where('email', $email)->first();

            //  Get the new password reset token
            $token = $password_reset->token;

            //  Generate the password reset endpoint and include the users token and email
            $password_reset_link = $password_reset_url.'?token='.$token.'&email='.urlencode($user->email);

            try {
                
                //  Send the password reset link to the user's email
                $sentPasswordResetLinkEmail = Mail::to( $user )->send(new SendPasswordResetLink($user, $password_reset_link));

                //  If the email was not sent successfully
                if (Mail::failures()) {    

                    //  Return fail response
                    return response()->json(['message' => 'We could not send your password reset link to the email provided. Please try again'], 404);
                
                }

                //  Return success response
                return response(['message' => 'Password reset link has been successfully sent to your email address "' . $email . '"']);

            } catch (\Throwable $e) {

                //  Handle error
                return response()->json(['message' => 'Could not reach the mail address to deliver the password reset link. Please try again'], 404);
            
            }

        }else{

            //  The account with the given email does not exist. Throw a validation error
            throw ValidationException::withMessages(['email' => 'The account using the email "'.$email.'" does not exist.']);

        }
    }

    public function resetPassword(Request $request)
    {
        //  Validate the user's inputs
        $userData = $request->validate([
            'email' => 'email|required',
            'password' => 'required|confirmed'
        ]);

        //  Get the user's token
        $token = $request->input('token');

        //  Get the user's email
        $email = $request->input('email');

        //  Get the user's password
        $password = $request->input('password');
    
        //  If the token was not provided
        if( !$token ){

            //  The token was not provided
            return response()->json(['message' => 'You need a token to reset your password'], 404);

        }
        
        //  Check if a user with the given email address exists
        if( \App\User::where('email', $email)->exists() ){

            //  Check if a user has a valid token
            if( DB::table('password_resets')->where('email', $email)->where('token', $token)->exists() ){

                //  Get the user
                $user = \App\User::where('email', $email)->first();
    
                //  Hash and update the new password
                $user->password = bcrypt($password);
                $user->save();
    
                //  Delete all password reset tokens
                DB::table('password_resets')->where('email', $email)->delete();
    
                //  Create new access token
                $accessToken = $user->createToken('authToken');
    
                //  Return response
                return response([
                    'user' => $user,
                    'access_token' => $accessToken
                ]);

            }else{

                //  The provided token is not valid
                return response()->json(['message' => 'The token provided is not valid or has expired'], 404);
    
            }

        }else{

            //  The provided token is not valid
            return response()->json(['message' => 'The account using the email "'.$email.'" does not exist.'], 404);

        }
    }
}
