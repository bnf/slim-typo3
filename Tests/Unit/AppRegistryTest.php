<?php
namespace Bnf\SlimTypo3\Tests\Unit;

use Bnf\SlimTypo3\AppRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * AppRegistryTest
 *
 * @author Benjamin Franzke <bfr@qbus.de>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class AppRegistryTest extends UnitTestCase
{
    public function testPushCallable()
    {
        $closure = function () {
        };
        $registry = new AppRegistry;
        $registry->push($closure);

        $this->assertSame($closure, $registry->pop());
    }

    public function testPushClassName()
    {
        $class = self::class;
        $registry = new AppRegistry;
        $registry->push($class);

        $this->assertEquals($class, $registry->pop());
    }
}

