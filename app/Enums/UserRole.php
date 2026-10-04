<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin   = 'admin';
    case Doctor  = 'doctor';
    case Patient          = 'patient';
    case Operator          = 'operator';
    case SupervisorDoctor  = 'supervisor_doctor';
}
