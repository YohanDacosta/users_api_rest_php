<?php

namespace App\Helpers;

abstract class Helpers
{
    static public function errorsPropertiesValidation($errors)
    {
        if (count($errors) > 0) {
            $errorList = [];

            foreach ($errors as $error) {
                $errorList[$error->getPropertyPath()] = $error->getMessage();
            }

            return $errorList;
        }

        return false;
    }
}