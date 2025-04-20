<?php

namespace App\Controller;

use App\DTO\UserDTO;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

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

    #[Route('/api/user/all', name: 'api_all_users', methods: 'GET')]
    public function index(): JsonResponse
    {
        $users = $this->em->getRepository(User::class)->findAll();
        $serializered = $this->serializer->serialize($users, 'json', ['groups' => 'user:read']);
        return new JsonResponse(['errors' => false, 'data' => json_decode($serializered)], Response::HTTP_OK);
    }

    #[Route('/api/user/view/{pk}', name: 'api_view_user', methods: 'GET')]
    public function detail(Request $request, $pk)
    {
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

    #[Route('/api/user/create', name: 'api_create_user', methods: 'POST')]
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

    #[Route('/api/user/edit', name: 'api_edit_user', methods: 'POST')]
    public function edit(Request $request) 
    {
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

    #[Route('/api/user/delete', name: 'api_delete_user', methods: ['POST'])]
    public function delete(Request $request) 
    {
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
}
