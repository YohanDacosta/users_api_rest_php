<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use App\Repository\UserRepository;

class UniqueEmailValidator extends ConstraintValidator 
{
    public function __construct(private UserRepository $userRepository) {}

    public function validate($value, Constraint $constraint) 
    {
        ### To obtain all the attributes of the DTO
        $dto = $this->context->getObject();
        $userByEmail = $this->userRepository->findOneBy(['email' => $value]);


        
        if ($userByEmail && $userByEmail->getId() != $dto->getId()) {
            $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $value)
            ->addViolation();
        }
    }
}
