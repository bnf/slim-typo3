<?php
declare(strict_types = 1);
namespace Bnf\SlimTypo3\Container;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;

final class ObjectManagerAdapter implements ContainerInterface
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param string $id
     * @return bool
     */
    public function has($id)
    {
        return $this->objectManager->isRegistered($id);
    }

    /**
     * @param string $id
     * @return mixed
     */
    public function get($id)
    {
        if ($this->objectManager->isRegistered($id) === false) {
            throw new NotFoundException('Container entry "' . $id . '" is not available.', 1519978105);
	}
        try {
            return $this->objectManager->get($id);
        } catch (\TYPO3\CMS\Extbase\Object\Exception $e) {
            throw new ContainerException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
