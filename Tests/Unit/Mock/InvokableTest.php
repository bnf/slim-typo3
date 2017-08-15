<?php
namespace Bnf\SlimTypo3\Tests\Unit\Mocks;

/**
 * Mock object for Bnf\SlimTypo3\Tests\Unit\CallableResolverTest
 */
class InvokableTest
{
    public static $CalledCount = 0;
    public function __invoke()
    {
        return static::$CalledCount++;
    }
}
