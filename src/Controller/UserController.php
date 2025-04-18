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
        return new JsonResponse(['errors' => false, 'data' => json_encode($serializered, true)], Response::HTTP_OK);
    }

    #[Route('/api/user/view', name: 'api_view_user', methods: 'GET')]
    public function detail()
    {

    }

    #[Route('/api/user/create', name: 'api_create_user', methods: 'POST')]
    public function new(Request $request) 
    {
        if ($request->isMethod('POST')) {
            $user = new User();
            $userDTO = $this->serializer->deserialize($request->getContent(), UserDTO::class, 'json');

            $errors = $this->validator->validate($userDTO);

            if (count($errors) > 0) {
                $errorList = [];

                foreach ($errors as $error) {
                    $errorList[$error->getPropertyPath()] = $error->getMessage();
                }

                return new JsonResponse(['errors' => true, 'data' => json_encode($errorList)], Response::HTTP_BAD_REQUEST);
            }

            $user->setFirstName($userDTO->first_name);
            $user->setLastName($userDTO->last_name);
            $user->setEmail($userDTO->email);
            $passwordHashed = $this->passwordHasher->hashPassword($user, $userDTO->password);
            $user->setPassword($passwordHashed);

            $this->em->persist($user);
            // $this->em->flush();

            $serializered = $this->serializer->serialize($user, 'json', ['groups' => 'user:read']);
            return new JsonResponse(['errors' => false, 'data' => json_decode($serializered)], Response::HTTP_CREATED);
        }

    }

    #[Route('/api/user/edit/{id}', name: 'api_edit_user', methods: ['POST', 'PUT'])]
    public function edit() 
    {

    }

    #[Route('/api/user/delete/{id}', name: 'api_delete_user', methods: ['POST', 'DELETED'])]
    public function delete() 
    {

    }
}
