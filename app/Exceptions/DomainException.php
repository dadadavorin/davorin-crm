<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Base for every exception representing a business-rule rejection, as
 * opposed to a framework or infrastructure failure. `ExceptionMap` maps
 * each concrete subclass to an HTTP status and a message key.
 */
abstract class DomainException extends RuntimeException {}
