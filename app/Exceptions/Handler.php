<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use KennedyOsaze\LaravelApiResponse\Concerns\ConvertsExceptionToApiResponse;
use KennedyOsaze\LaravelApiResponse\Concerns\RendersApiResponse;
use Throwable;

class Handler extends ExceptionHandler
{
    use ConvertsExceptionToApiResponse, RendersApiResponse;

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


        $this->renderable(function (Throwable $e, $request) {
            if ($request->wantsJson()) {
                if ($e instanceof CustomException) {
                    return $this->clientErrorResponse($e->getMessage());
                };
                return $this->renderApiResponse($e, $request);
            }
        });
    }

    protected function invalidJson($request, ValidationException $exception): Response|JsonResponse
    {
        if ($request->wantsJson()) {
            return $this->renderApiResponse($exception, $request);
        }
        return parent::invalidJson($request, $exception);
    }
}
