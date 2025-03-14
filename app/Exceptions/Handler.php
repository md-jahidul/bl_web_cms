<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

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
     * @param  \Exception $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Exception $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {

        if ($exception instanceof \Illuminate\Http\Exceptions\PostTooLargeException) {
            return response()->json((['status' => 'FAIL', 'status_code' => 500, 'message' => 'Server file size limit exceded.']), 500);
        }
        
        if (env('APP_ENV') !== 'local') {
            if ($exception instanceof \PDOException) {
                return response()->json((['status' => 'FAIL', 'status_code' => 500, 'message' => 'Sorry, cannot perform the action, something went wrong with data!']), 500);
            }

            if ($exception instanceof FatalErrorException) {
                return response()->json((['status' => 'FAIL', 'status_code' => 500, 'message' => 'Sorry, cannot perform the action, something went wrong!']), 500);
            }

            

        }
        return parent::render($request, $exception);
    }


    /**
     * NotFoundHttpException
     * The requested path could not match a route in the API
     *
     * @param NotFoundHttpException $exception
     * @return void 403
     */

    protected function handleNotFoundHttpException(NotFoundHttpException $exception)
    {
        abort(403);
    }

}
