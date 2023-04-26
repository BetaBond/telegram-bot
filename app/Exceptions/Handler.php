<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;


class Handler extends ExceptionHandler
{
    
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];
    
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];
    
    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];
    
    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
    
    /**
     * Creates a response object from the given validation exception.
     *
     * @param  ValidationException  $e
     * @param  Request  $request
     * @return \Illuminate\Http\Response|JsonResponse|Response|null
     */
    protected function convertValidationExceptionToResponse(
        ValidationException $e,
        $request
    ): \Illuminate\Http\Response|JsonResponse|Response|null {
        
        $message = $e->validator->errors()->first();
        Log::info("convertValidationExceptionToResponse: $message");
        
        if ($e->response) {
            return $e->response;
        }
        
        return null;
    }
    
}
