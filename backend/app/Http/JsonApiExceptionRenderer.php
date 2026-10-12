<?php

namespace App\Http;

use App\Data\JsonApiErrorData;
use App\Data\JsonApiErrorDocumentData;
use App\Data\JsonApiErrorSourceData;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Optional;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class JsonApiExceptionRenderer
{
    public function __invoke(Throwable $e, Request $request): JsonResponse
    {
        return match (true) {
            $e instanceof ValidationException => $this->respond(422, $this->validationErrors($e)),
            $e instanceof AuthenticationException => $this->respond(
                401,
                [new JsonApiErrorData('401', 'Unauthenticated', 'A valid bearer token is required.')],
                ['WWW-Authenticate' => 'Bearer'],
            ),
            $e instanceof HttpExceptionInterface => $this->respond($e->getStatusCode(), [$this->httpError($e)], $e->getHeaders()),
            default => $this->respond(500, [new JsonApiErrorData('500', 'Server Error')]),
        };
    }

    /**
     * @param  list<JsonApiErrorData>  $errors
     * @param  array<string, string>  $headers
     */
    private function respond(int $status, array $errors, array $headers = []): JsonResponse
    {
        return new JsonResponse(
            (new JsonApiErrorDocumentData($errors))->toArray(),
            $status,
            ['Content-Type' => 'application/vnd.api+json'] + $headers,
        );
    }

    private function httpError(HttpExceptionInterface $e): JsonApiErrorData
    {
        $status = $e->getStatusCode();

        return new JsonApiErrorData(
            status: (string) $status,
            title: Response::$statusTexts[$status] ?? 'Error',
            detail: $e->getMessage() !== '' ? $e->getMessage() : new Optional,
        );
    }

    /**
     * @return list<JsonApiErrorData>
     */
    private function validationErrors(ValidationException $e): array
    {
        $errors = [];

        foreach ($e->errors() as $key => $messages) {
            foreach ($messages as $message) {
                $errors[] = match (true) {
                    str_starts_with($key, 'data.') => new JsonApiErrorData('422', 'Invalid attribute', $message, source: new JsonApiErrorSourceData(pointer: '/'.str_replace('.', '/', $key))),
                    default => new JsonApiErrorData('422', 'Invalid parameter', $message, source: new JsonApiErrorSourceData(parameter: preg_replace('/\.([^.]+)/', '[$1]', $key) ?? $key)),
                };
            }
        }

        return $errors;
    }
}
