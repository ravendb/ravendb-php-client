<?php

namespace RavenDB\Documents\Session;

use RavenDB\Type\ObjectMap;

class JavaScriptArray
{
    private ?int $suffix = null;
    private int $argCounter = 0;

    private ?string $pathToArray = null;

    private array $scriptLines = [];
    private ?ObjectMap $parameters = null;

    public function __construct(int $suffix, ?string $pathToArray)
    {
        $this->parameters = new ObjectMap();

        $this->suffix = $suffix;
        $this->pathToArray = $pathToArray;
    }

    /**
     * @param mixed $values
     * @return JavaScriptArray
     */
    public function add(...$values): JavaScriptArray
    {
        if (count($values) == 1 && is_array($values[0])) {
            $values = $values[0];
        }

        $args = [];
        foreach ($values as $value) {
            $argumentName = $this->getNextArgumentName();
            $this->parameters->offsetSet($argumentName, $value);
            $args[] = "args." . $argumentName;
        }

        $this->scriptLines[] = "this." . $this->pathToArray . ".push(" . implode(',', $args) . ");";

        return $this;
    }

    public function removeAt(int $index): JavaScriptArray
    {
        $argumentName = $this->getNextArgumentName();

        $this->scriptLines[] = "this." . $this->pathToArray . ".splice(args." . $argumentName . ", 1);";
        $this->parameters->offsetSet($argumentName, $index);

        return $this;
    }

    private function getNextArgumentName(): string
    {
        return "val_" . $this->argCounter++ . "_" . $this->suffix;
    }

    public function getScript(): string
    {
        return implode("\r", $this->scriptLines);
    }

    public function getParameters(): ObjectMap
    {
        return $this->parameters;
    }
}
