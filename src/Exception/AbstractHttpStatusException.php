<?php

declare(strict_types=1);

namespace Letkode\HttpExceptionBundle\Exception;

use Letkode\HttpExceptionBundle\Contract\HttpStatusExceptionInterface;
use Letkode\HttpExceptionBundle\Trait\HttpStatusExceptionTrait;

abstract class AbstractHttpStatusException extends \RuntimeException implements HttpStatusExceptionInterface
{
    use HttpStatusExceptionTrait;
}
