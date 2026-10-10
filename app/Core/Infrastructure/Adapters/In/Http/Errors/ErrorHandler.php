<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\In\Http\Errors;

use App\Core\Application\Exceptions\ConcurrencyConflict;
use App\Core\Application\Exceptions\NotFound;
use App\Core\Domain\Exceptions\BusinessRuleViolation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class ErrorHandler
{
    public static function render(Throwable $e): Response|JsonResponse
    {
        if ($e instanceof ValidationFailedException) {
            if ($e->isOnlyPathError) {
                return new Response('', 404, ['Content-Length' => '0']);
            }

            $fields = array_keys($e->errors);
            $detail = 'Datos de entrada no válidos: '.implode(', ', $fields).'.';

            return new JsonResponse([
                'title' => 'Datos de entrada no válidos',
                'status' => 400,
                'detail' => $detail,
                'errors' => $e->errors,
            ], 400, [
                'Content-Type' => 'application/problem+json; charset=utf-8',
            ]);
        }

        if ($e instanceof ValidationException) {
            $formattedErrors = [];
            foreach ($e->errors() as $field => $messages) {
                // Remove root prefix like body. or query.
                $cleanField = preg_replace('/^(body|query)\./', '', $field) ?? $field;
                $formattedErrors[$cleanField] = $messages;
            }

            $fields = array_keys($formattedErrors);
            $detail = 'Datos de entrada no válidos: '.implode(', ', $fields).'.';

            return new JsonResponse([
                'title' => 'Datos de entrada no válidos',
                'status' => 400,
                'detail' => $detail,
                'errors' => $formattedErrors,
            ], 400, [
                'Content-Type' => 'application/problem+json; charset=utf-8',
            ]);
        }

        if ($e instanceof BusinessRuleViolation) {
            return new JsonResponse([
                'title' => 'Regla de negocio violada',
                'status' => 422,
                'detail' => $e->getMessage(),
            ], 422, [
                'Content-Type' => 'application/problem+json; charset=utf-8',
            ]);
        }

        if ($e instanceof ConcurrencyConflict) {
            return new JsonResponse([
                'title' => 'Conflicto con otra operación simultánea',
                'status' => 409,
                'detail' => 'Otra operación modificó los datos al mismo tiempo. Inténtalo de nuevo.',
            ], 409, [
                'Content-Type' => 'application/problem+json; charset=utf-8',
            ]);
        }

        if ($e instanceof NotFound || $e instanceof NotFoundHttpException) {
            return new Response('', 404, ['Content-Length' => '0']);
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            $headers = $e->getHeaders();
            $headers['Content-Length'] = '0';

            return new Response('', 405, $headers);
        }

        if ($e instanceof HttpExceptionInterface) {
            $statusCode = $e->getStatusCode();
            if (in_array($statusCode, [401, 403, 404, 405], true)) {
                $headers = $e->getHeaders();
                $headers['Content-Length'] = '0';

                return new Response('', $statusCode, $headers);
            }
        }

        // Generic 500 error
        Log::error('Unhandled exception in API: '.$e->getMessage(), [
            'exception' => $e,
        ]);

        return new JsonResponse([
            'title' => 'Error interno',
            'status' => 500,
            'detail' => 'Ocurrió un error inesperado.',
        ], 500, [
            'Content-Type' => 'application/problem+json; charset=utf-8',
        ]);
    }
}
