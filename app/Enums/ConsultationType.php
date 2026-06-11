<?php

namespace App\Enums;

enum ConsultationType: string
{
    case Presencial   = 'presencial';
    case Videollamada = 'videollamada';
    case Chat         = 'chat';
}
