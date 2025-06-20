<?php

namespace App\Controller\Api;

use App\DTO\EventDTO;
use App\DTO\UserDTO;
use App\Entity\Event;
use App\Entity\User;
use App\Helpers\Constants;
use App\Helpers\Helpers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class UserController extends AbstractController
{
    private $em;
    private $serializer;
    private $validator;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer, ValidatorInterface $validator, UserPasswordHasherInterface $passwordHasher) {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->passwordHasher = $passwordHasher;

    }

    /** Validation of the token JWT */
    #[Route('/token/validate', name:"token_validate")]
    public function validate(UserInterface $user) 
    {
        if (!$user instanceof UserInterface) {
           return;
        }

        $_user = array(
            'firstname' => $user->getFirstName(),
            'lastname' => $user->getLastName(),
            'email' => $user->getEmail(),
            'image' => $user->getImageName(),
        );

        return $this->json([
            'errors' => false, 
            'data' => $_user
        ], Response::HTTP_OK); 
    }

    /** Show all users */
    #[Route('/user/all', name: 'api_all_users')]
    public function index(Request $request): JsonResponse
    {
        if ($request->getMethod() != 'GET') {
            return $this->json([
                'errors' => true,
                'message' => Constants::ERROR_METHOD_NOT_ALLOWED,
                'data' => null,
            ], Response::HTTP_METHOD_NOT_ALLOWED);
        }

        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json([
                'errors' => true, 
                'message' => Constants::ERROR_DENIED_ACCESS,
                'data' => null
            ], Response::HTTP_FORBIDDEN);
        } 

        $users = $this->em->getRepository(User::class)->findAll();
        
        return $this->json([
            'errors' => false, 
            'message' => null, 
            'data' => $users
        ], Response::HTTP_OK, [], ['groups' => 'user:read']);
    }

    /** Show an user by ID */
    #[Route('/user/view/{pk}', name: 'api_view_user')]
    public function detail(Request $request, $pk): JsonResponse
    {
        if ($request->getMethod() != 'GET') {
            return $this->json([
                'errors' => true,
                'message' => Constants::ERROR_METHOD_NOT_ALLOWED,
                'data' => null,
            ], Response::HTTP_METHOD_NOT_ALLOWED);
        }

        if (!$this->isGranted('ROLE_USER')) {
            return $this->json([
                'errors' => true, 
                'message' => Constants::ERROR_DENIED_ACCESS,
                'data' => null
            ], Response::HTTP_FORBIDDEN);
        }

        $userDTO = new UserDTO();
        $userDTO->setId($pk);
        $validated =  $this->validator->validate(
            $userDTO, 
            null, 
            ['user:validate']
        );

        $errors = Helpers::errorsPropertiesValidation($validated);

        if ($errors) {
            return $this->json([
                'errors' => true, 
                'message' => $errors, 
                'data' => null
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['id' => $userDTO->getID()]);

        if (!$user) {
            return $this->json([
                'errors' => true, 
                'message' => Constants::ERROR_USER_DOESNT_EXITS, 
                'data' => null
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'errors' => false, 
            'data' => $user
        ], Response::HTTP_OK, [], ['groups' => 'user:read']);
    }

    /** Create an User by ID */
    #[Route('/user/create', name: 'api_create_user')]
    public function new(Request $request): JsonResponse 
    {
        if ($request->getMethod() != 'POST') {
            return $this->json([
                'errors' => true,
                'message' => Constants::ERROR_METHOD_NOT_ALLOWED,
                'data' => null,
            ], Response::HTTP_METHOD_NOT_ALLOWED);
        }

        $userDTO = $this->serializer->deserialize(
            $request->getContent(), 
            UserDTO::class, 
            'json'
        );
        $validated = $this->validator->validate(
            $userDTO, 
            null, 
            ['user:create']
        );

        $errors = Helpers::errorsPropertiesValidation($validated);

        if ($errors) {
            return $this->json([
                'errors' => true, 
                'message' => $errors, 
                'data' => null
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setFirstName($userDTO->getFirstName());
        $user->setLastName($userDTO->getLastName());
        $user->setEmail($userDTO->getEmail());
        $passwordHashed = $this->passwordHasher->hashPassword($user, $userDTO->getPassword());
        $user->setPassword($passwordHashed);

        $this->em->persist($user);
        $this->em->flush();

        return $this->json([
            'errors' => false, 
            'data' => $user
        ], Response::HTTP_CREATED, [], ['groups' => 'user:read']);
    }

    /** Update an user by ID */
    #[Route('/user/edit', name: 'api_edit_user')]
    public function edit(Request $request): JsonResponse
    {
        if ($request->getMethod() != 'PUT') {
            return $this->json([
                'errors' => true,
                'message' => Constants::ERROR_METHOD_NOT_ALLOWED,
                'data' => null,
            ], Response::HTTP_METHOD_NOT_ALLOWED);
        }

        if (!$this->isGranted('ROLE_USER')) {
            return $this->json([
                'errors' => true, 
                'message' => Constants::ERROR_DENIED_ACCESS,
                'data' => null
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            /** @var UserDTO $userDTO */
            $userDTO = $this->serializer->deserialize(
                $request->getContent(), 
                UserDTO::class, 
                'json'
            );
        } catch (\Throwable $e) {
            return $this->json([
                'errors' => true, 
                'message' => Constants::ERROR_INVALID_FORMAT,
                'data' => null
            ], Response::HTTP_BAD_REQUEST);
        }

        $validated = $this->validator->validate(
            $userDTO, 
            null, 
            ['user:validate', 'user:update']
        );

        $errors = Helpers::errorsPropertiesValidation($validated);

        if ($errors) {
            return $this->json([
                'errors' => true, 
                'message' => $errors, 
                'data' => null
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['id' => $userDTO->getId()]);
        
        if (!$user) {
            return $this->json([
                'errors' => true,
                'message' => Constants::ERROR_USER_DOESNT_EXITS,
                'data' => null
            ]);
        }

        $user->setFirstName($userDTO->getFirstName());
        $user->setLastName($userDTO->getLastName());
        $user->setEmail($userDTO->getEmail());
        $this->em->flush();

        return $this->json([
            'errors' => false, 
            'message' => null, 
            'data' => $user
        ], Response::HTTP_OK, [], ['groups' => 'user:read']);
    }

    /** Delete an user by ID */
    #[Route('/user/delete', name: 'api_delete_user')]
    public function delete(Request $request): JsonResponse
    {
        if ($request->getMethod() != 'DELETE') {
            return $this->json([
                'errors' => true,
                'message' => Constants::ERROR_METHOD_NOT_ALLOWED,
                'data' => null,
            ], Response::HTTP_METHOD_NOT_ALLOWED);
        }

        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json([
                'errors' => true, 
                'message' => Constants::ERROR_DENIED_ACCESS,
                'data' => null
            ], Response::HTTP_FORBIDDEN);
        }

        $userDTO = $this->serializer->deserialize(
            $request->getContent(), 
            UserDTO::class, 
            'json'
        );
        $validated = $this->validator->validate(
            $userDTO, 
            null, 
            ['user:validate'
        ]);

        $errors = Helpers::errorsPropertiesValidation($validated);

        if ($errors) {
            return $this->json([
                'errors' => true, 
                'message' => $errors, 
                'data' => null
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['id' => $userDTO->getId()]);
        
        if (!$user) {
            return $this->json([
                'errors' => true,
                'message' => Constants::ERROR_USER_DOESNT_EXITS,
                'data' => null
            ]);
        }

        $this->em->remove($user);
        $this->em->flush();
        
        return $this->json([
            'errors' => false, 
            'message' => null, 
            'data' => null
        ], Response::HTTP_NO_CONTENT);
    }

    /** Disable an user by ID */
    #[Route('/user/disable', name: 'api_disable_user')]
    public function disable(Request $request): JsonResponse
    {
        if ($request->getMethod() != 'POST') {
            return $this->json([
                'errors' => true,
                'message' => Constants::ERROR_METHOD_NOT_ALLOWED,
                'data' => null,
            ], Response::HTTP_METHOD_NOT_ALLOWED);
        }

        if (!$this->isGranted('ROLE_USER')) {
            return $this->json([
                'errors' => true, 
                'message' => Constants::ERROR_DENIED_ACCESS,
                'data' => null
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            /** @var UserDTO $userDTO */
            $userDTO = $this->serializer->deserialize(
                $request->getContent(), 
                UserDTO::class, 
                'json'
            );
        } catch (\Throwable $e) {
            return $this->json([
                'errors' => true, 
                'message' => Constants::ERROR_INVALID_FORMAT,
                'data' => null
            ]);
        }
        $validated = $this->validator->validate(
            $userDTO, 
            null, 
            ['user:validate']
        );

        $errors = Helpers::errorsPropertiesValidation($validated);

        if ($errors) {
            return $this->json([
                'errors' => true, 
                'message' => $errors, 
                'data' => null
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['id' => $userDTO->getId()]);

        if (!$user) {
            return $this->json([
                'errors' => true,
                'message' => Constants::ERROR_USER_DOESNT_EXITS,
                'data' => null
            ]);
        }
        $user->setIsDeleted(true);
        $this->em->flush();

        return $this->json([
            'errors' => false, 
            'message' => null, 
            'data' => null
        ], Response::HTTP_NO_CONTENT);
    }

    /** Upload image by ID */
    #[Route('/user/upload_image', name: 'api_upload_image_user')]
    public function upload(Request $request): JsonResponse
    {
        if ($request->getMethod() != 'POST') {
            return $this->json([
                'errors' => true,
                'message' => Constants::ERROR_METHOD_NOT_ALLOWED,
                'data' => null,
            ], Response::HTTP_METHOD_NOT_ALLOWED);
        }

        if (!$this->isGranted('ROLE_USER')) {
            return $this->json([
                'errors' => true, 
                'message' => Constants::ERROR_DENIED_ACCESS,
                'data' => null
            ], Response::HTTP_FORBIDDEN);
        }

        /** @var UploadedFile $image */
        $image = $request->files->get('image');

        if (!$image) {
            return $this->json([
                'errors' => true, 
                'message' => Constants::ERROR_NOT_FILE_UPLOADED, 
                'data' => null
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            /** @var UserDTO $userDTO */
            $userDTO = $this->serializer->deserialize(
                json_encode($request->request->all()), 
                UserDTO::class, 
                'json'
            );
        } catch (\Throwable $e) {
            return $this->json([
                'errors' => true,
                'message' => Constants::ERROR_INVALID_FORMAT,
                'data' => null
            ]);
        }
        $validated = $this->validator->validate(
            $userDTO, 
            null, 
            ['user:validate']
        );

        $errors = Helpers::errorsPropertiesValidation($validated);

        if ($errors) {
            return $this->json([
                'errors' => true, 
                'message' => $errors, 
                'data' => null
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['id' => $userDTO->getId()]);

        if (!$user) {
            return $this->json([
                'errors' => true, 
                'message' => Constants::ERROR_USER_DOESNT_EXITS, 
                'data' => null
            ], Response::HTTP_NOT_FOUND);
        }
        $user->setImage($image);
        $this->em->persist($user);
        $this->em->flush();

        return $this->json([
            'errors' => false, 
            'message' => null, 
            'data' => $user->getImageName()
        ], Response::HTTP_OK);
    }

    /** Saved event by user ID and event ID */
    #[Route('/user/saved_event/{pk}', name: 'api_saved_event')]
    public function saved_event_by_id(Request $request, $pk): JsonResponse
    {
        if ($request->getMethod() != 'GET') {
            return $this->json([
                'errors' => true,
                'message' => Constants::ERROR_METHOD_NOT_ALLOWED,
                'data' => null,
            ], Response::HTTP_METHOD_NOT_ALLOWED);
        }

        $userDTO = new UserDTO();
        $userDTO->setId($pk);

        $validatedUser = $this->validator->validate(
            $userDTO, 
            null, 
            ['user:validate'
        ]);

        $errors = Helpers::errorsPropertiesValidation($validatedUser);

        if ($errors) {
            return $this->json([
                'errors' => true, 
                'message' => $errors, 
                'data' => null
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['id' => $pk]);

        if (!$user) {
            return $this->json([
                'errors' => true,
                'message' => Constants::ERROR_USER_DOESNT_EXITS,
                'data' => null
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            /** @var EventDTO $eventDTO */
            $eventDTO = $this->serializer->deserialize(
                $request->getContent(), 
                EventDTO::class, 
                'json'
            );
        } catch (\Throwable $e) {
            return $this->json([
                'errors' => true,
                'message' => Constants::ERROR_INVALID_FORMAT,
                'data' => null
            ], Response::HTTP_BAD_REQUEST);
        }
        $validatedEvent = $this->validator->validate(
            $eventDTO, 
            null, 
            ['event:validate']
        );

        $errors = Helpers::errorsPropertiesValidation($validatedEvent);

        if ($errors) {
            return $this->json([
                'errors' => true, 
                'message' => $errors, 
                'data' => null
            ], Response::HTTP_BAD_REQUEST);
        }

        $event = $this->em->getRepository(Event::class)->findOneBy(['id' => $eventDTO->getId()]);

        if (!$event) {
            return $this->json([
                'errors' => true, 
                'data' => null, 
                'message' => Constants::ERROR_EVENT_DOESNT_EXITS
            ], Response::HTTP_NOT_FOUND);
        }

        $user->addSavedEvent($event);
        $this->em->persist($user);
        $this->em->flush();

        return $this->json([
            'errors' => false, 
            'message' => null, 
            'data' => $user
        ], Response::HTTP_OK, [], ['groups' => 'user:read']);
    }
    
    /** Unsaved event by user ID and event ID */
    #[Route('/user/unsaved_event/{pk}', name: 'api_unsaved_event')]
    public function unsaved_event_by_id(Request $request, $pk): JsonResponse
    {
        if ($request->getMethod() != 'GET') {
            return $this->json([
                'errors' => true,
                'message' => Constants::ERROR_METHOD_NOT_ALLOWED,
                'data' => null,
            ], Response::HTTP_METHOD_NOT_ALLOWED);
        }

        $userDTO = new UserDTO();
        $userDTO->setId($pk);

        $validatedUser = $this->validator->validate(
            $userDTO, 
            null, 
            ['user:validate']
        );

        $errors = Helpers::errorsPropertiesValidation($validatedUser);

        if ($errors) {
            return $this->json([
                'errors' => true, 
                'message' => $errors, 
                'data' => null
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['id' => $pk]);
        
        if (!$user) {
            return $this->json([
                'errors' => true, 
                'message' => Constants::ERROR_USER_DOESNT_EXITS,
                'data' => null
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            /** @var EventDTO $eventDTO */
            $eventDTO = $this->serializer->deserialize(
                $request->getContent(), 
                EventDTO::class, 
                'json'
            );
        } catch (\Throwable $e) {
            return $this->json([
                'errors' => true,
                'message' => Constants::ERROR_INVALID_FORMAT,
                'data' => null,
            ]);
        }

        $validatedEvent = $this->validator->validate(
            $eventDTO, 
            null, 
            ['event:validate']
        );

        $errors = Helpers::errorsPropertiesValidation($validatedEvent);

        if ($errors) {
            return $this->json([
                'errors' => true, 
                'message' => $errors, 
                'data' => null
            ], Response::HTTP_BAD_REQUEST);
        }

        $event = $this->em->getRepository(Event::class)->findOneBy(['id' => $eventDTO->getId()]);

        if (!$event) {
            return $this->json([
                'errors' => true, 
                'message' => Constants::ERROR_EVENT_DOESNT_EXITS,
                'data' => null
            ], Response::HTTP_NOT_FOUND);
        }

        $user->removeSavedEvent($event);
        $this->em->flush();

        return $this->json([
            'errors' => false, 
            'message' => null, 
            'data' => $user
        ], Response::HTTP_OK, [], ['groups' => 'user:read']);
    }

    /** Add an event to the user's attendee list  */
    #[Route('/user/attendee_event/{pk}', name: 'api_attendee_event')]
    public function attendee_event_by_id(Request $request, $pk): JsonResponse
    {
        if ($request->getMethod() != 'GET') {
            return $this->json([
                'errors' => true,
                'message' => Constants::ERROR_METHOD_NOT_ALLOWED,
                'data' => null,
            ], Response::HTTP_METHOD_NOT_ALLOWED);
        }

        $userDTO = new UserDTO();
        $userDTO->setId($pk);

        $validatedUser = $this->validator->validate($userDTO, null, ['user:validate']);

        $errors = Helpers::errorsPropertiesValidation($validatedUser);

        if ($errors) {
            return $this->json([
                'errors' => true, 
                'message' => $errors, 
                'data' => null
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['id' => $pk]);

        if (!$user) {
            return $this->json([
                'errors' => true, 
                'message' => Constants::ERROR_USER_DOESNT_EXITS,
                'data' => null
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            /** @var EventDTO $eventDTO */
            $eventDTO = $this->serializer->deserialize(
                $request->getContent(), 
                EventDTO::class, 
                'json'
            );
        } catch (\Throwable $e) {
            return $this->json([
                'errors' => true,
                'message' => Constants::ERROR_INVALID_FORMAT,
                'data' => null,
            ], Response::HTTP_BAD_REQUEST);
        }

        $validatedEvent = $this->validator->validate($eventDTO, null, ['event:validate']);

        $errors = Helpers::errorsPropertiesValidation($validatedEvent);

        if ($errors) {
            return $this->json([
                'errors' => true, 
                'message' => $errors, 
                'data' => null
            ], Response::HTTP_BAD_REQUEST);
        }

        $event = $this->em->getRepository(Event::class)->findOneBy(['id' => $eventDTO->getId()]);

        if (!$event) {
            return $this->json([
                'errors' => true, 
                'message' => Constants::ERROR_EVENT_DOESNT_EXITS,
                'data' => null
            ], Response::HTTP_NOT_FOUND);
        }

        $user->addAttendeeEventsId($event);
        $this->em->persist($user);
        $this->em->flush();

        return $this->json([
            'errors' => false, 
            'data' => $user
        ], Response::HTTP_OK, [], ['groups' => 'user:read']);
    }

    /** Delete an event to the user's unattendee list  */
    #[Route('/user/unattended_event/{pk}', name: 'api_unattended_event')]
    public function unattended_event_by_id(Request $request, $pk): JsonResponse
    {
        if ($request->getMethod() != 'GET') {
            return $this->json([
                'errors' => true,
                'message' => Constants::ERROR_METHOD_NOT_ALLOWED,
                'data' => null,
            ], Response::HTTP_METHOD_NOT_ALLOWED);
        }

        $userDTO = new UserDTO();
        $userDTO->setId($pk);

        $validatedUser = $this->validator->validate($userDTO, null, ['user:validate']);

        $errors = Helpers::errorsPropertiesValidation($validatedUser);

        if ($errors) {
            return $this->json([
                'errors' => true, 
                'message' => $errors, 
                'data' => null
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['id' => $pk]);

        if (!$user) {
            return $this->json([
                'errors' => true, 
                'message' => Constants::ERROR_USER_DOESNT_EXITS,
                'data' => null
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            /** @var EventDTO $eventDTO */
            $eventDTO = $this->serializer->deserialize(
                $request->getContent(), 
                EventDTO::class, 
                'json'
            );
        } catch (\Throwable $e) {
            return $this->json([
                'errors' => true,
                'message' => Constants::ERROR_INVALID_FORMAT,
                'data' => null,
            ], Response::HTTP_BAD_REQUEST);
        }

        $validatedEvent = $this->validator->validate($eventDTO, null, ['event:validate']);

        $errors = Helpers::errorsPropertiesValidation($validatedEvent);

        if ($errors) {
            return $this->json([
                'errors' => true, 
                'message' => $errors, 
                'data' => null
            ], Response::HTTP_BAD_REQUEST);
        }

        $event = $this->em->getRepository(Event::class)->findOneBy(['id' => $eventDTO->getId()]);

        if (!$event) {
            return $this->json([
                'errors' => true, 
                'message' => Constants::ERROR_EVENT_DOESNT_EXITS,
                'data' => null
            ], Response::HTTP_NOT_FOUND);
        }

        $user->removeAttendeeEventsId($event);
        $this->em->flush();

        return $this->json([
            'errors' => false, 
            'message' => null, 
            'data' => $user
        ], Response::HTTP_OK, [], ['groups' => 'user:read']);
    }
}
