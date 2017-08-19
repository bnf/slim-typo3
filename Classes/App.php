<?php
declare(strict_types=1);
namespace Bnf\SlimTypo3;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * App
 *
 * @author Benjamin Franzke <bfr@qbus.de>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class App
{
    /**
     * Register a new App
     *
     * @param  callable|string $callable
     * @return void
     * @deprecated since 0.2.0, will be removed when leaving 0.x
     */
    public static function register($callable)
    {
        GeneralUtility::deprecationLog(self::class . '::register() is deprecated. Will be supported only for 0.x releases. And removed with the next major versio number.');
        GeneralUtility::makeInstance(AppRegistry::class)->unshift($callable);
    }
}
