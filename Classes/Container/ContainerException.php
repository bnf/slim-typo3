<?php
declare(strict_types = 1);
namespace Bnf\SlimTypo3\Container;

use Exception;
use Psr\Container\ContainerExceptionInterface;

class ContainerException extends Exception implements ContainerExceptionInterface
{
}
