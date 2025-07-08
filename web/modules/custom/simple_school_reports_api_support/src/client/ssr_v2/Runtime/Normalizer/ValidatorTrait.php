<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Normalizer;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validation;
trait ValidatorTrait
{
    protected function validate(array $data, Constraint $constraint): void
    {
        $validator = Validation::createValidator();
        $violations = $validator->validate($data, $constraint);
        if ($violations->count() > 0) {
            throw new ValidationException($violations);
        }
    }
}