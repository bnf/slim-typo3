<?php
namespace Bnf\SlimTypo3\Tests\Unit\Mocks;

/**
 * Mock object for Bnf\SlimTypo3\Tests\Unit\CallableResolverTest
 */
class CallableTest
{
    public static $CalledCount = 0;

    public static $CalledContainer = null;

    public function __construct($container = null)
    {
        static::$CalledContainer = $container;
    }

    public function toCall()
    {
        return static::$CalledCount++;
    }
}
