<?php

namespace App\EventListener;

use App\Helpers\Constants;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class CustomExceptionListener 
{
    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();

            if ($statusCode == 400) {
                $event->setResponse(new JsonResponse([
                    'errors' => true,
                    'message' => Constants::ERROR_EMPTY_FIELDS,
                    'data' => null,
                ], Response::HTTP_BAD_REQUEST));
            }

            if ($statusCode == 404) {
                $event->setResponse(new JsonResponse([
                    'errors' => true,
                    'message' => $exception->getMessage(),
                    'data' => null,
                ], Response::HTTP_NOT_FOUND));
            }

            if ($statusCode == 405) {
                $event->setResponse(new JsonResponse([
                    'errors' => true,
                    'message' => Constants::ERROR_METHOD_NOT_ALLOWED,
                    'data' => null,
                ], Response::HTTP_METHOD_NOT_ALLOWED));
            }
        }
    }
}
