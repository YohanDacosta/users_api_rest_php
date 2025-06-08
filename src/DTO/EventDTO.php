<?php

namespace App\DTO;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

class EventDTO
{
    #[Assert\NotBlank(message: 'The id field should not be blank.', groups: ['event:validate'])]
    #[Assert\Uuid(message: 'This is not a valid ID', groups: ['event:validate'])]
    private ?string $id = null;

    #[Assert\NotBlank(message: "The title field should not be blank.", groups: ['event:create', 'event:update'])]
    private string $title;

    #[Assert\NotBlank(message: "The subtitle field should not be blank.", groups: ['event:create', 'event:update'])]
    private string $subtitle;
   
    #[Assert\NotBlank(message: "The location field should not be blank.", groups: ['event:create', 'event:update'])]
    private string $location;

    private string $description;

    #[Assert\File(
        maxSize: "2M",
        mimeTypes: ["image/jpeg", "image/jpg", "image/png"],
        mimeTypesMessage: "Only images of type (jpeg, JPEG , PNG).",
        maxSizeMessage: "The image must not be major than 2MB.",
        groups: ['event:create', 'event:update']
    )]
    private ?File $imageFile = null;

    public function getId() 
    {
        return $this->id;
    }

    public function setId(string $value) 
    {
        $this->id = $value;
        return $this->id;
    }

    public function getTitle() 
    {
        return $this->title;
    }

    public function setTitle(string $value) 
    {
        $this->title = $value;
        return $this->title;
    }

    public function getSubtitle() 
    {
        return $this->subtitle;
    }

    public function setSubtitle(string $value) 
    {
        $this->subtitle = $value;
        return $this->subtitle;
    }

    public function getLocation() 
    {
        return $this->location;
    }

    public function setLocation(string $value) 
    {
        $this->location = $value;
        return $this->location;
    }

    public function getDescription() 
    {
        return $this->description;
    }

    public function setDescription(string $value) 
    {
        $this->description = $value;
        return $this->description;
    }

    public function getImage() 
    {
        return $this->imageFile;
    }

    public function setImage(?File $image) 
    {
        $this->imageFile = $image;
        return $this->imageFile;
    }
}
