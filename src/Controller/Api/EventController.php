<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\DTO\EventDTO;
use App\Helpers\Constants;
use App\Helpers\Helpers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class EventController extends AbstractController
{
    private $em;
    private $serializer;
    private $validator;

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer, ValidatorInterface $validator)
    {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    /** Show all events */
    #[Route('/event/all', name: 'api_all_events', methods: 'GET')]
    public function index(): JsonResponse
    {
        $events = $this->em->getRepository(Event::class)->findAll();

        $serializered = $this->serializer->serialize(
            $events, 
            'json', 
            ['groups' => 'event:read']
        );

        return $this->json([
            'errors' => false, 
            'message' => null, 
            'data' => json_decode($serializered)
        ], Response::HTTP_OK);
    }   

    /** Create an event */
    #[Route('/event/create', name: 'api_create_event', methods: 'POST')]
    public function new(Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse([
                'errors' => true, 
                'message' => Constants::ERROR_DENIED_ACCESS,
                'data' => null
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            /** @var EventDTO $eventDTO */
            $eventDTO = $this->serializer->deserialize(
                json_encode($request->request->all()), 
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

        /** @var UploadedFile $image */
        $image = $request->files->get('image');
        
        if ($image) {
            $eventDTO->setImage($image);
        }
        $validatedEvent = $this->validator->validate($eventDTO, null, ['event:create']);

        $errors = Helpers::errorsPropertiesValidation($validatedEvent);

        if ($errors) {
            return $this->json([
                'errors' => true, 
                'message' => $errors, 
                'data' => null
            ], Response::HTTP_BAD_REQUEST);
        }

        $event = new Event();
        $event->setTitle($eventDTO->getTitle());
        $event->setSubtitle($eventDTO->getSubtitle());
        $event->setLocation($eventDTO->getLocation());
        $event->setDescription($eventDTO->getDescription());
        $event->setImage($eventDTO->getImage());

        $this->em->persist($event);
        $this->em->flush();

        $serializered = $this->serializer->serialize($event, 'json', ['groups' => 'event:read']);
        
        return $this->json([
            'errors' => false, 
            'message' => null, 
            'data' => json_decode($serializered)
        ], Response::HTTP_CREATED);
    }

    /** Upload image by event ID */
    #[Route('/event/upload_image', name: 'api_upload_image_event', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse([
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
            /** @var EventDTO $eventDTO */
            $eventDTO = $this->serializer->deserialize(
                json_encode($request->request->all()), 
                EventDTO::class, 
                'json'
            );
        } catch (\Throwable $e) {
            return $this->json([
                'errors' => true, 
                'message' => Constants::ERROR_INVALID_FORMAT
            ], Response::HTTP_BAD_REQUEST);
        }

        $eventDTO->setImage($image);
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

        $event->setImage($eventDTO->getImage());
        $this->em->flush();
        
        return $this->json([
            'errors' => false, 
            'message' => null,
            'data' => $event->getImageName()
        ], Response::HTTP_OK);
    }
}