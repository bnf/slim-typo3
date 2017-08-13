<?php
namespace Bnf\SlimTypo3\Hook;

/**
 * Interface ConfigureAppHookInterface
 * @author Benjamin Franzke <bfr@qbus.de>
 */
interface ConfigureAppHookInterface
{
    /**
     * @param \Slim\App $app
     */
    public static function configure(\Slim\App $app);
}
