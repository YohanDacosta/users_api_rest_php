<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;

class CustomAuthenticationFailureListener
{
 public function onAuthenticationFailure(AuthenticationFailureEvent $event)
 {
    $response = new JsonResponse([
        'errors' => true,
        'message' => 'Incorrect email or password.',
        'data' => null,
    ]);

    $event->setResponse($response);
 }
}