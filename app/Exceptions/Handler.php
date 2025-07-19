<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Convert a validation exception into a response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Validation\ValidationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function convertValidationExceptionToResponse($exception, $request)
    {
        if ($exception instanceof ValidationException) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => $exception->getMessage(),
                    'errors' => $exception->errors(),
                ], 422);
            }

            // For web requests, redirect back with errors and input
            return redirect()->back()
                ->withInput($request->except($this->dontFlash))
                ->withErrors($exception->errors());
        }

        return parent::convertValidationExceptionToResponse($exception, $request);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        // For API requests, always return JSON error responses
        if ($request->expectsJson() || $request->is('api/*')) {
            $status = $this->isHttpException($e) ? $e->getStatusCode() : 500;

            return response()->json([
                'error' => $e->getMessage(),
            ], $status);
        }

        return parent::render($request, $e);
    }
}
