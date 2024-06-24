<?php


namespace Lemonade\Pdf\QRBuilder\Traits;

use Exception;
use Lemonade\Pdf\QRBuilder\Exceptions\QRCodeException;
use ReflectionClass;

/**
 * ClassLoader
 * \Lemonade\Pdf\QRBuilder\Traits\ClassLoader
 */
trait ClassLoader
{

    /**
     * @param string $class
     * @param string|null $type
     * @param ...$params
     * @return mixed
     * @throws QRCodeException
     */
    public function loadClass(string $class, string $type = null, ...$params): mixed
    {
        $type = $type === null ? $class : $type;

        try {
            $reflectionClass = new ReflectionClass($class);
            $reflectionType = new ReflectionClass($type);

            if ($reflectionType->isTrait()) {
                trigger_error($class . ' cannot be an instance of trait ' . $type);
            }

            if ($reflectionClass->isAbstract()) {
                trigger_error('cannot instance abstract class ' . $class);
            }

            if ($reflectionClass->isTrait()) {
                trigger_error('cannot instance trait ' . $class);
            }

            if ($class !== $type) {

                if ($reflectionType->isInterface() && !$reflectionClass->implementsInterface($type)) {
                    trigger_error($class . ' does not implement ' . $type);
                } elseif (!$reflectionClass->isSubclassOf($type)) {
                    trigger_error($class . ' does not inherit ' . $type);
                }

            }

            $object = $reflectionClass->newInstanceArgs($params);

            if (!$object instanceof $type) {
                trigger_error('how did u even get here?'); // @codeCoverageIgnore
            }

            return $object;
        } catch (Exception $e) {
            throw new QRCodeException('ClassLoader: ' . $e->getMessage());
        }

    }

}
