<?php

declare(strict_types=1);

namespace App\Core\Domain\Exceptions;

/**
 * Base of every business rule violation. The HTTP adapter maps it to 422 and
 * puts the message in "detail", so messages are the contract texts (Spanish).
 */
abstract class BusinessRuleViolation extends \RuntimeException {}
