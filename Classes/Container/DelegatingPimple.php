<?php
declare(strict_types = 1);
namespace Bnf\SlimTypo3\Container;

use Pimple\Container;
use Psr\Container\ContainerInterface;

final class DelegatingPimple extends Container implements ContainerInterface
{
    /** @var ContainerInterface */
    private $delegate;

    public function __construct(array $values = array(), ContainerInterface $delegate = null)
    {
        parent::__construct($values);
        $this->delegate = $delegate ?? $this;
    }

    /**
     * @param strimg
     * @return mixed
     */
    public function offsetGet($id)
    {
        return $this->delegate->get($id);
    }

    /**
     * @param strimg
     * @return bool
     */
    public function offsetExists($id)
    {
        return $this->delegate->has($id);
    }

    /**
     * @param string $id
     * @return bool
     */
    public function has($id)
    {
        return parent::offsetExists($id);
    }

    /**
     * @param string $id
     * @return mixed
     */
    public function get($id)
    {
        return parent::offsetGet($id);
    }
}
