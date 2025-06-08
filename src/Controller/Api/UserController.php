<?php

namespace App\Controller\Api;

use App\DTO\EventDTO;
use App\DTO\UserDTO;
use App\Entity\Event;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\UserInterface;

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

        return new JsonResponse(['errors' => false, 'data' => $_user], Response::HTTP_OK); 
    }

    #[Route('/user/all', name: 'api_all_users', methods: 'GET')]
    public function index(): JsonResponse
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            $users = $this->em->getRepository(User::class)->findAll();
            $serializered = $this->serializer->serialize($users, 'json', ['groups' => 'user:read']);
            return new JsonResponse(['errors' => false, 'data' => json_decode($serializered)], Response::HTTP_OK);
        } 
        return new JsonResponse(['errors' => true, 'data' => null, 'message' => 'Access denied. You do not have permission to access this resource.'], Response::HTTP_FORBIDDEN);
    }

    #[Route('/user/view/{pk}', name: 'api_view_user', methods: 'GET')]
    public function detail(Request $request, $pk)
    {
        if ($this->isGranted('ROLE_USER')) {
            
            if (Uuid::isValid($pk)) {
                $user = $this->em->getRepository(User::class)->findOneBy(['id' => $pk]);
    
                if ($user) {
                    $serializered = $this->serializer->serialize($user, 'json', ['groups' => 'user:read']);
    
                    return new JsonResponse(['errors' => false, 'data' => json_decode($serializered)], Response::HTTP_OK);
                }
    
                return new JsonResponse(['errors' => true, 'data' => null], Response::HTTP_NOT_FOUND);
            }
            return new JsonResponse(['errors' => true, 'data' => null], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['errors' => true, 'data' => null, 'message' => 'Access denied. You do not have permission to access this resource.'], Response::HTTP_FORBIDDEN);
    }

    #[Route('/user/create', name: 'api_create_user', methods: 'POST')]
    public function new(Request $request) 
    {
        if ($request->isMethod('POST')) {
            $user = new User();
            $userDTO = $this->serializer->deserialize($request->getContent(), UserDTO::class, 'json');

            $errors = $this->validator->validate($userDTO, null, ['user:create']);

            if (count($errors) > 0) {
                $errorList = [];

                foreach ($errors as $error) {
                    $errorList[$error->getPropertyPath()] = $error->getMessage();
                }

                return new JsonResponse(['errors' => true, 'message' => $errorList, 'data' => null], Response::HTTP_BAD_REQUEST);
            }
            $user->setFirstName($userDTO->getFirstName());
            $user->setLastName($userDTO->getLastName());
            $user->setEmail($userDTO->getEmail());
            $passwordHashed = $this->passwordHasher->hashPassword($user, $userDTO->getPassword());
            $user->setPassword($passwordHashed);

            $this->em->persist($user);
            $this->em->flush();

            $serializered = $this->serializer->serialize($user, 'json', ['groups' => 'user:read']);
            return new JsonResponse(['errors' => false, 'data' => json_decode($serializered)], Response::HTTP_CREATED);
        }

    }

    #[Route('/user/edit', name: 'api_edit_user', methods: 'POST')]
    public function edit(Request $request) 
    {
        if ($this->isGranted('ROLE_USER')) {

            if ($request->isMethod('POST')) {
                $userDTO = $this->serializer->deserialize($request->getContent(), UserDTO::class, 'json');
                $errors = $this->validator->validate($userDTO, null, ['user:validate', 'user:update']);
    
                if (count($errors) > 0) {
                    $errorList = [];
    
                    foreach ($errors as $error) {
                        $errorList[$error->getPropertyPath()] = $error->getMessage();
                    }
    
                    return new JsonResponse(['errors' => true, 'message' => $errorList, 'data' => null], Response::HTTP_BAD_REQUEST);
                }
    
                $user = $this->em->getRepository(User::class)->findOneBy(['id' => $userDTO->getId()]);
                
                if ($user) {
                    $user->setFirstName($userDTO->getFirstName());
                    $user->setLastName($userDTO->getLastName());
                    $user->setEmail($userDTO->getEmail());
                    $this->em->flush();
    
                    $serializered = $this->serializer->serialize($user, 'json', ['groups' => 'user:read']);
    
                    return new JsonResponse(['errors' => false, 'data' => json_decode($serializered)], Response::HTTP_OK);
                }
            }
        }

        return new JsonResponse(['errors' => true, 'data' => null, 'message' => 'Access denied. You do not have permission to access this resource.'], Response::HTTP_FORBIDDEN);

    }

    #[Route('/user/delete', name: 'api_delete_user', methods: ['POST'])]
    public function delete(Request $request) 
    {
        if ($this->isGranted('ROLE_ADMIN')) {

            if ($request->isMethod('POST')) {
                $userDTO = $this->serializer->deserialize($request->getContent(), UserDTO::class, 'json');
                $errors = $this->validator->validate($userDTO, null, ['user:validate']);
    
                if (count($errors) > 0) {
                    $errorList = [];
    
                    foreach ($errors as $error) {
                        $errorList[$error->getPropertyPath()] = $error->getMessage();
                    }
    
                    return new JsonResponse(['errors' => true, 'message' => $errorList, 'data' => null], Response::HTTP_BAD_REQUEST);
                }
    
                $user = $this->em->getRepository(User::class)->findOneBy(['id' => $userDTO->getId()]);
                
                if ($user) {
                    $this->em->remove($user);
                    $this->em->flush();
    
                    return new JsonResponse(['errors' => false, 'data' => null], Response::HTTP_NO_CONTENT);
                }
            }
    
            return new JsonResponse(['errors' => true, 'data' => null], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['errors' => true, 'data' => null, 'message' => 'Access denied. You do not have permission to access this resource.'], Response::HTTP_FORBIDDEN);
    }

    #[Route('/user/disable')]
    public function disable(Request $request)
    {
        if ($this->isGranted('ROLE_USER')) {
            
            if ($request->getMethod('POST')) {
                $userDTO = $this->serializer->deserialize($request->getContent(), UserDTO::class, 'json');
                $errors = $this->validator->validate($userDTO, null, ['user:validate']);
    
                if (count($errors) > 0) {
                    $errorList = [];
    
                    foreach ($errors as $error) {
                        $errorList[$error->getPropertyPath()] = $error->getMessage();
                    }
    
                    return new JsonResponse(['errors' => true, 'message' => $errorList, 'data' => null], Response::HTTP_BAD_REQUEST);
                }
    
                $user = $this->em->getRepository(User::class)->findOneBy(['id' => $userDTO->getId()]);
                
                if ($user) {
                    $user->setIsDeleted(true);
                    $this->em->flush();
    
                    return new JsonResponse(['errors' => false, 'data' => null], Response::HTTP_NO_CONTENT);
                }
            }

            return new JsonResponse(['errors' => true, 'data' => null], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['errors' => true, 'data' => null, 'message' => 'Access denied. You do not have permission to access this resource.'], Response::HTTP_FORBIDDEN);
    }

    #[Route('/user/upload_image')]
    public function upload(Request $request)
    {
        if ($this->isGranted('ROLE_USER')) {

            if ($request->getMethod('POST')) {
                /** @var UploadedFile $file */
                $image = $request->files->get('image');
        
                if ($image) {
                    $userDTO = $this->serializer->deserialize(json_encode($request->request->all()), UserDTO::class, 'json');
                    $errors = $this->validator->validate($userDTO, null, ['user:validate']);
        
                    if (count($errors) > 0) {
                        $errorList = [];
        
                        foreach ($errors as $error) {
                            $errorList[$error->getPropertyPath()] = $error->getMessage();
                        }
        
                        return new JsonResponse(['errors' => true, 'message' => $errorList, 'data' => null], Response::HTTP_BAD_REQUEST);
                    }
        
                    $user = $this->em->getRepository(User::class)->findOneBy(['id' => $userDTO->getId()]);

                    if ($user) {
                        $user->setImage($image);
                        $this->em->persist($user);
                        $this->em->flush();

                        return new JsonResponse(['errors' => false, 'data' => $user->getImageName()], Response::HTTP_OK);
                    }
                }
        
                return new JsonResponse(['errors' => true, 'messages' =>'No file uploaded', 'data' => null], Response::HTTP_BAD_REQUEST);
            }

            return new JsonResponse(['errors' => true, 'data' => null], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['errors' => true, 'data' => null, 'message' => 'Access denied. You do not have permission to access this resource.'], Response::HTTP_FORBIDDEN);
    }

    #[Route('/user/saved_event/{pk}', name: 'api_saved_event', methods: 'GET')]
    public function saved_event_by_id(Request $request, $pk)
    {
            $userDTO = new UserDTO();
            $userDTO->setId($pk);
            $errors = $this->validator->validate($userDTO, null, ['user:validate']);

            if (count($errors) > 0) {
                $errorList = [];

                foreach ($errors as $error) {
                    $errorList[$error->getPropertyPath()] = $error->getMessage();
                }

                return new JsonResponse(['errors' => true, 'message' => $errorList, 'data' => null], Response::HTTP_BAD_REQUEST);
            }

            $user = $this->em->getRepository(User::class)->findOneBy(['id' => $pk]);

            if ($user) {
                $eventDTO = $this->serializer->deserialize($request->getContent(), EventDTO::class, 'json');
                $errors = $this->validator->validate($eventDTO, null, ['event:validate']);

                if (count($errors) > 0) {
                    $errorList = [];

                    foreach ($errors as $error) {
                        $errorList[$error->getPropertyPath()] = $error->getMessage();
                    }

                    return new JsonResponse(['errors' => true, 'message' => $errorList, 'data' => null], Response::HTTP_BAD_REQUEST);
                }

                $event = $this->em->getRepository(Event::class)->findOneBy(['id' => $eventDTO->getId()]);

                if ($event) {
                    $user->addSavedEvent($event);
                    $this->em->persist($user);
                    $this->em->flush();

                    return new JsonResponse(['errors' => false, 'data' => []], Response::HTTP_OK);
                }

                return new JsonResponse(['errors' => true, 'data' => null, 'message' => 'Event does not exist.'], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse(['errors' => true, 'data' => null, 'message' => 'User does not exist.'], Response::HTTP_NOT_FOUND);
        }

}
