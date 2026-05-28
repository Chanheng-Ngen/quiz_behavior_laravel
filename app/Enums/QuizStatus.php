<?php

namespace App\Enums;

enum QuizStatus: string
{
    case ACTIVE = 'active';
    case DRAFT  = 'draft';
    case CLOSED = 'closed';
}