<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Illuminate\Http\Request;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        AuthenticationException::class,
        ValidationException::class,
        ModelNotFoundException::class,
        HttpException::class,
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'pin',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Log full details server-side only — never exposed to client
        });
    }

    public function render($request, Throwable $e)
    {
        // API requests get clean JSON errors only
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->renderApiError($request, $e);
        }

        // Rate limiting
        if ($e instanceof TooManyRequestsHttpException) {
            return response()->view('errors.429', [
                'retryAfter' => $e->getHeaders()['Retry-After'] ?? 60,
            ], 429);
        }

        // Not found
        if ($e instanceof NotFoundHttpException || $e instanceof ModelNotFoundException) {
            return response()->view('errors.404', [], 404);
        }

        // Auth
        if ($e instanceof AuthenticationException) {
            return redirect()->route('login')->with('error', 'Please sign in to continue.');
        }

        // Validation — pass through normally (these show field errors, not exceptions)
        if ($e instanceof ValidationException) {
            return parent::render($request, $e);
        }

        // All other exceptions — show generic "something went wrong" page
        // Log the real error server-side with a reference code
        $errorRef = strtoupper(substr(md5(uniqid()), 0, 8));
        logger()->error("Error ref: {$errorRef}", [
            'exception' => $e->getMessage(),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'trace'     => $e->getTraceAsString(),
            'url'       => $request->fullUrl(),
            'user'      => auth()->id(),
        ]);

        return response()->view('errors.500', [
            'errorRef' => $errorRef,
        ], 500);
    }

    private function renderApiError(Request $request, Throwable $e): \Illuminate\Http\JsonResponse
    {
        if ($e instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        }

        if ($e instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please sign in.',
            ], 401);
        }

        if ($e instanceof ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found.',
            ], 404);
        }

        if ($e instanceof TooManyRequestsHttpException) {
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please slow down.',
            ], 429);
        }

        $errorRef = strtoupper(substr(md5(uniqid()), 0, 8));
        logger()->error("API Error ref: {$errorRef}", ['exception' => $e->getMessage(), 'user' => auth()->id()]);

        return response()->json([
            'success'  => false,
            'message'  => 'Something went wrong. Please try again.',
            'ref'      => $errorRef,
        ], 500);
    }
}
