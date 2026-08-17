<?php

namespace App\Entity\Enum;

enum ContactType: string
{
    case Person = 'person';
    case LegalEntity = 'legal_entity';
}
