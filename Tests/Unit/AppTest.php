<?php
namespace Bnf\SlimTypo3\Tests\Unit;

use Bnf\SlimTypo3\App;
use Bnf\SlimTypo3\AppRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * AppTest
 *
 * @author Benjamin Franzke <bfr@qbus.de>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class AppTest extends UnitTestCase
{
    public function testRegisterStoresClosure()
    {
        $closure = function () {
        };

        $old = set_error_handler(function() {});
        App::register($closure);
        set_error_handler($old);

        $this->assertSame($closure, GeneralUtility::makeInstance(AppRegistry::class)->pop());
    }
}
