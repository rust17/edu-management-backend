<?php

namespace App\Enums;

enum Business: int
{
    case SUCCESS = 0;
    case ERROR = 1;
    case LOGIN_FAILED = 2;
}
