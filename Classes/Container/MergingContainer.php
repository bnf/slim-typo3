<?php
declare(strict_types = 1);
namespace Bnf\SlimTypo3\Container;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final class MergingContainer implements ContainerInterface
{
    /** @var ContainerInterface[] */
    private $containers = [];

    public function add(ContainerInterface $container): void
    {
        $this->containers[] = $container;
    }

    /**
     * @param string $id
     * @return bool
     */
    public function has($id)
    {
        foreach ($this->containers as $container) {
            if ($container->has($id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $id
     * @return mixed
     */
    public function get($id)
    {
        $previous = null;

        foreach ($this->containers as $container) {
            if ($container->has($id)) {
                try {
                    return $container->get($id);
                } catch (NotFoundExceptionInterface $e) {
                    $previous = $e;
                    // Try next container
                    continue;
                }
            }
        }

        if ($previous !== null) {
            throw new NotFoundException(
                'Failed to create container entry "' . $id . '". Reason: ' . $previous->getMessage(),
                1566397347,
                $previous
            );
        }

        throw new NotFoundException('Container entry "' . $id . '" is not available.', 1566324513);
    }
}
