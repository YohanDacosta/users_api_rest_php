<?php

namespace App\DTO;

use App\Validator\Constraints as AppAssert;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UserDTO 
{
    #[Assert\NotBlank(message: 'The id field should not be blank.', groups: ['user:validate'])]
    #[Assert\Uuid(message: 'This is not a valid ID', groups: ['user:validate'])]
    private ?string $id = null;

    #[Assert\NotBlank(message: "The first_name field should not be blank.", groups: ['user:create', 'user:update'])]
    private string $first_name;

    #[Assert\NotBlank(message: 'The last_name field should not be blank.', groups: ['user:create', 'user:update'])]
    private string $last_name;

    #[Assert\NotBlank(message: 'Email should not be blank.', groups: ['user:create'])]
    #[Assert\Email(message: 'The email field {{ value }} is not a valid email.', groups: ['user:create'])]
    #[AppAssert\UniqueEmail(groups: ['user:create', 'user:update'])]
    private string $email;

    #[Assert\NotBlank(message: 'The password field should not be blank.', groups: ['user:create'])]
    #[Assert\Length(min: 8, groups: ['user:create'])]
    private string $password;

    #[Assert\NotBlank(message: 'The confirm_password field should not be blank.', groups: ['user:create'])]
    private string $confirm_password;

    #[Assert\File(
        maxSize: "2M",
        mimeTypes: ["image/jpeg", "image/png"],
        mimeTypesMessage: "Only images of type JPG o PNG.",
        maxSizeMessage: "The image must not be major than 2MB."
    )]
    private ?File $image = null;

    #[Assert\Callback(groups: ['user:create'])]
    private function validate(ExecutionContextInterface $context): void
    {
        if ($this->password !== $this->confirm_password) {
            $context->buildViolation('Passwords do not match.')
            ->atPath('confirm_password')
            ->addViolation();
        }
    }

    public function getID()
    {
        return $this->id;
    }

    public function setId(string $value)
    {
        $this->id = $value;
        return $this->id;
    }

    public function getFirstName()
    {
        return $this->first_name;
    }

    public function setFirstName(string $value)
    {
        $this->first_name = $value;
        return $this->first_name;
    }

    public function getLastName()
    {
        return $this->last_name;
    }

    public function setLastName(string $value)
    {
        $this->last_name = $value;
        return $this->last_name;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail(string $value)
    {
        $this->email = $value;
        return $this->email;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword(string $value)
    {
        $this->password = $value;
        return $this->password;
    }

    public function getConfirmPassword()
    {
        return $this->confirm_password;
    }

    public function setConfirmPassword(string $value)
    {
        $this->confirm_password = $value;
        return $this->confirm_password;
    }
}
