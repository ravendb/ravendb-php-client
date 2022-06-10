<?php

namespace tests\RavenDB\Test\Issues\RavenDB_11649Test;

class OuterClass
{
        private ?InnerClassMatrix $innerClassMatrix = null;
        private ?InnerClassArray $innerClasses = null;
        private ?string $a = null;
        private ?InnerClass $innerClass = null;
        private ?MiddleClass $middleClass = null;

    public function & getInnerClassMatrix(): ?InnerClassMatrix
    {
        return $this->innerClassMatrix;
    }

    public function setInnerClassMatrix(?InnerClassMatrix $innerClassMatrix): void
    {
        $this->innerClassMatrix = $innerClassMatrix;
    }

    public function & getInnerClasses(): ?InnerClassArray
    {
        return $this->innerClasses;
    }

    public function setInnerClasses(?InnerClassArray $innerClasses): void
    {
        $this->innerClasses = $innerClasses;
    }

    public function getA(): ?string
    {
        return $this->a;
    }

    public function setA(?string $a): void
    {
        $this->a = $a;
    }

    public function getInnerClass(): ?InnerClass
    {
        return $this->innerClass;
    }

    public function setInnerClass(?InnerClass $innerClass): void
    {
        $this->innerClass = $innerClass;
    }

    public function getMiddleClass(): ?MiddleClass
    {
        return $this->middleClass;
    }

    public function setMiddleClass(?MiddleClass $middleClass): void
    {
        $this->middleClass = $middleClass;
    }
}
