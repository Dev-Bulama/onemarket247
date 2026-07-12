<?php

namespace App\Support\Api;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

/**
 * Standard OneMarket247 API response envelope, applied uniformly across
 * every /api/v1/* endpoint (see docs/architecture/01-system-architecture.md §6
 * and docs/architecture/08-api-endpoints.md).
 */
class ApiResponse
{
    public static function success(mixed $data = null, array $meta = [], ?string $message = null, int $status = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => $meta === [] ? null : $meta,
            'message' => $message,
        ], $status);
    }

    public static function error(string $message, array $errors = [], string $errorCode = 'ERROR', int $status = Response::HTTP_UNPROCESSABLE_ENTITY): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors' => $errors,
            'error_code' => $errorCode,
        ], $status);
    }

    public static function exception(Throwable $e): JsonResponse
    {
        return match (true) {
            $e instanceof ValidationException => self::error(
                $e->getMessage(),
                $e->errors(),
                'VALIDATION_FAILED',
                Response::HTTP_UNPROCESSABLE_ENTITY,
            ),
            $e instanceof AuthenticationException => self::error(
                'Unauthenticated.',
                [],
                'UNAUTHENTICATED',
                Response::HTTP_UNAUTHORIZED,
            ),
            $e instanceof AuthorizationException => self::error(
                $e->getMessage() ?: 'This action is unauthorized.',
                [],
                'FORBIDDEN',
                Response::HTTP_FORBIDDEN,
            ),
            $e instanceof ModelNotFoundException, $e instanceof NotFoundHttpException => self::error(
                'The requested resource was not found.',
                [],
                'NOT_FOUND',
                Response::HTTP_NOT_FOUND,
            ),
            $e instanceof TooManyRequestsHttpException => self::error(
                'Too many requests.',
                [],
                'RATE_LIMITED',
                Response::HTTP_TOO_MANY_REQUESTS,
            ),
            $e instanceof HttpExceptionInterface => self::error(
                $e->getMessage() ?: 'An error occurred.',
                [],
                'HTTP_ERROR',
                $e->getStatusCode(),
            ),
            default => self::error(
                app()->hasDebugModeEnabled() ? $e->getMessage() : 'Server Error.',
                [],
                'SERVER_ERROR',
                Response::HTTP_INTERNAL_SERVER_ERROR,
            ),
        };
    }
}
