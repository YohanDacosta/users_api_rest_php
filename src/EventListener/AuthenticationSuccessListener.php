<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthenticationSuccessListener {

    /**
     * @param RequestStack $requestStack
     */
    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event)
    {
        $isStaff = false;
        $data = $event->getData();
        $user = $event->getUser();

        if (!$user instanceof UserInterface) {
            return;
        }

        if (in_array('ROLE_ADMIN', $user->getRoles(), true) || in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
           $isStaff = true;
        }

        $data['user'] = array(
            'id' => $user->getId(),
            'firstname' => $user->getFirstName(),
            'lastname' => $user->getLastName(),
            'email' => $user->getEmail(),
            'image' => $user->getImageName(),
            'isStaff' => $isStaff,
        );

        $event->setData($data);
    }
}