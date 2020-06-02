<?php

namespace App\Exceptions;

use Throwable;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\ThrottleRequestsException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {

        /** API Error handling and reporting
         *  
         *  Here we define the returned JSON response for each exception
         *  encountered during an API Call
         * 
         */
        if ($request->expectsJson()) {

            switch (true) {

                /*  Error handle if the model data does not exist. Helpful for error handling especially for
                 *  Route-Model binding scenerios e.g create(Company $company){} but the resource is not found
                 */
                case $e instanceof ModelNotFoundException:
                    //  Found inside helper.php function
                    return help_model_not_fonud();
                    break;

                /*  Error handle if the route does not exist
                 */
                case $e instanceof NotFoundHttpException:
                    //  Found inside helper.php function
                    return help_route_not_fonud();
                    break;

                /*  Error handle if the request method is not supported
                 */
                case $e instanceof MethodNotAllowedHttpException:
                    //  Found inside helper.php function
                    return help_method_not_allowed();
                    break;

                /*  Error handle if the resource relationship is not found
                 *  e.g) when we use Model::with($relationship) and its not found
                 */
                case $e instanceof RelationNotFoundException:
                    //  Found inside helper.php function
                    return help_model_relationship_not_fonud();
                    break;

                /*  Error handle too many requests have been made by a client on the api
                 */
                case $e instanceof ThrottleRequestsException:
                    //  Found inside helper.php function
                    return help_to_many_login_attempts();
                    break;
            }
        }

        return parent::render($request, $e);
    }
}
