<?php


namespace Lemonade\Pdf\QRBuilder\Traits;

use ReflectionException;
use ReflectionProperty;

/**
 * Container
 * \Lemonade\Pdf\QRBuilder\Traits\Container
 */
trait Container
{

    /**
     * @param array|null $properties
     * @throws ReflectionException
     */
    public function __construct(array $properties = null)
    {
        if (!empty($properties)) {
            foreach ($properties as $key => $value) {
                $this->__set($key, $value);
            }
        }
    }

    /**
     * @param string $property
     * @return mixed
     * @throws ReflectionException
     */
    public function __get(string $property)
    {
        return $this->__isset($property) ? $this->{$property} : null;
    }

    /**
     * @param string $property
     * @param mixed $value
     * @return void
     * @throws ReflectionException
     */
    public function __set(string $property, mixed $value)
    {
        // avoid overwriting private properties
        if (!property_exists($this, $property) || !$this->__isPrivate($property)) {

            $this->{$property} = $value;
        }
    }

    /**
     * @param string $property
     * @return bool
     * @throws ReflectionException
     */
    public function __isset(string $property)
    {
        return (property_exists($this, $property) && !$this->__isPrivate($property));
    }

    /**
     * @param string $property
     * @return bool
     * @throws ReflectionException
     */
    protected function __isPrivate(string $property): bool
    {
        return (new ReflectionProperty($this, $property))->isPrivate();
    }

    /**
     * @param string $property
     * @return void
     * @throws ReflectionException
     */
    public function __unset(string $property)
    {

        if ($this->__isPrivate($property)) {

            unset($this->{$property});
        }
    }

    /**
     * @return false|string
     * @throws ReflectionException
     */
    public function __toString()
    {
        return json_encode($this->__toArray());
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    public function __toArray(): array
    {
        $data = [];

        foreach ($this as $property => $value) {
            if ($this->__isset($property)) {

                $data[$property] = $value;
            }
        }

        return $data;
    }

}
