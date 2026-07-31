<?php

namespace App\Exceptions;

use Exception;

// Thrown for ANY login failure — unknown TIN, unknown email, wrong password, or
// an inactive company. Carrying a single generic reason ensures the API never
// reveals which field was wrong (see CLAUDE.md §8).
class InvalidCredentialsException extends Exception
{
    //
}
