<?php

namespace PHPSpec2\Subject;

use ReflectionClass;

use PHPSpec2\Exception\Exception;
use PHPSpec2\Exception\ClassNotFoundException;
use PHPSpec2\Formatter\Presenter\PresenterInterface;

class LazyObject implements LazySubjectInterface
{
    private $classname;
    private $arguments;
    private $presenter;
    private $instance;
    private $instantiatorName;

    public function __construct($classname = null, array $arguments = array(),
                                PresenterInterface $presenter)
    {
        $this->classname = $classname;
        $this->arguments = $arguments;
        $this->presenter = $presenter;
    }

    public function getInstantiatorName()
    {
        return $this->instantiatorName;
    }

    public function setInstantiatorName($instantiatorName)
    {
        $this->instantiatorName = $instantiatorName;
    }

    public function setClassname($classname)
    {
        $this->classname = $classname;
    }

    public function getClassname()
    {
        return $this->classname;
    }

    public function setConstructorArguments(array $arguments)
    {
        $this->arguments = $arguments;
    }

    public function getConstructorArguments()
    {
        return $this->arguments;
    }

    public function getInstance()
    {
        if ($this->instance) {
            return $this->instance;
        }

        if (null === $this->classname || !is_string($this->classname)) {
            throw new Exception(sprintf(
                'Instantiator expects class name, got %s.',
                $this->presenter->presentValue($this->classname)
            ));
        }

        if (!class_exists($this->classname)) {
            throw new ClassNotFoundException(sprintf(
                'Class %s does not exists.', $this->presenter->presentString($this->classname)
            ), $this->classname);
        }

        $reflection = new ReflectionClass($this->classname);
        if (
            !!($this->instantiatorName)
            && $reflection->hasMethod($this->instantiatorName)
            && !!($constructor = $reflection->getConstructor())
            && !($constructor->isPublic())
        ) {
            $method = $reflection->getMethod($this->instantiatorName);
            if (!$method->isPublic() && !$method->isStatic()) {
                throw new MethodNotFoundException(sprintf(
                    'Class %s does not have instantiator: %s.',
                    $this->presenter->presentString($this->classname),
                    $this->presenter->presentString($this->instantiatorName)
                ), $this->classname, $this->instantiatorName, $this->arguments);
            }
            if (empty($this->arguments)) {
                return $this->instance = $method->invoke(null);
            }
            return $this->instance = $method->invokeArgs(null, $this->arguments);
        }

        if (!empty($this->arguments)) {
            return $this->instance = $reflection->newInstanceArgs($this->arguments);
        }

        return $this->instance = $reflection->newInstance();
    }
}
