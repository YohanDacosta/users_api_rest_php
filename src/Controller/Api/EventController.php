<?php

namespace App\Controller\Api;

use App\DTO\EventDTO;
use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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

    #[Route('/event/all', name: 'api_all_events', methods: 'GET')]
    public function index(): JsonResponse
    {
        $events = $this->em->getRepository(Event::class)->findAll();

        $serializered = $this->serializer->serialize($events, 'json', ['groups' => 'event:read']);
        return new JsonResponse(['errors' => false, 'data' => json_decode($serializered)], Response::HTTP_OK);
    }   

    #[Route('/event/create', name: 'api_create_event', methods: 'POST')]
    public function new(Request $request){
        if ($this->isGranted('ROLE_ADMIN')) {
            if ($request->getMethod('POST')){
                
                if ($request->request->all()) {
                    
                    $eventDTO = $this->serializer->deserialize(json_encode($request->request->all()), EventDTO::class, 'json');
                    /** @var UploadedFile $file */
                    $image = $request->files->get('image');

                    
                    if ($image) {
                        $eventDTO->setImage($image);
                    }
                    $errors = $this->validator->validate($eventDTO, null, ['event:create']);

                    if (count($errors) > 0) {
                        $errorList = [];

                        foreach ($errors as $error) {
                            $errorList[$error->getPropertyPath()] = $error->getMessage();
                        }

                        return new JsonResponse(['errors' => true, 'data' => null, 'message' => $errorList], Response::HTTP_BAD_REQUEST);
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
                    
                    return new JsonResponse(['errors' => false, 'data' => json_decode($serializered)], Response::HTTP_CREATED);
                }

                return new JsonResponse(['errors' => true, 'data' => null, 'message' => 'There are empty fields.'], Response::HTTP_BAD_REQUEST);
            }
        }

        return new JsonResponse(['errors' => true, 'data' => null, 'message' => 'Access denied. You do not have permission to access this resource.'], Response::HTTP_FORBIDDEN);
    }
}
