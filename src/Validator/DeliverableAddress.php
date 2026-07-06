<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class DeliverableAddress extends Constraint
{
    public string $message = 'Cette adresse ne correspond pas à une commune livrable Hodina.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
