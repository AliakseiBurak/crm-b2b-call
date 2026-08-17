<?php

namespace App\Entity\Enum;

enum GroupType: string
{
    case User = 'user';
    case Custom = 'custom';
}
