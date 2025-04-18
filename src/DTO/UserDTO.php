<?php

namespace App\DTO;

use App\Validator\Constraints as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UserDTO 
{
    #[Assert\NotBlank(message: "The first_name field should not be blank.")]
    public string $first_name;

    #[Assert\NotBlank(message: 'The last_name field should not be blank.')]
    public string $last_name;

    #[Assert\NotBlank(message: 'Email should not be blank.')]
    #[Assert\Email(message: 'The email field {{ value }} is not a valid email.')]
    #[AppAssert\UniqueEmail]
    public string $email;

    #[Assert\NotBlank(message: 'The password field should not be blank.')]
    #[Assert\Length(min: 8)]
    public string $password;

    #[Assert\NotBlank(message: 'The confirm_password field should not be blank.')]
    public string $confirm_password;

    #[Assert\Callback]
    private function validate(ExecutionContextInterface $context): void
    {
        if ($this->password !== $this->confirm_password) {
            $context->buildViolation('Passwords do not match.')
            ->atPath('confirm_password')
            ->addViolation();
        }
    }



}
