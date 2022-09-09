<?php

namespace RavenDB\Documents\Session;

use RavenDB\Type\ObjectMap;

class JavaScriptMap
{
    private ?int $suffix = null;
    private int $argCounter = 0;

    private ?string $pathToMap = null;

    private array $scriptLines = [];
    private ?ObjectMap $parameters = null;

    public function __construct(int $suffix, ?string $pathToMap)
    {
        $this->parameters = new ObjectMap();

        $this->suffix = $suffix;
        $this->pathToMap = $pathToMap;
    }

    public function put($key, $value): JavaScriptMap
    {
        $argumentName = $this->getNextArgumentName();

        $this->scriptLines[] = "this." . $this->pathToMap . "." . $key . " = args." . $argumentName . ";";
        $this->parameters[$argumentName] = $value;
        return $this;
    }

    public function remove($index): JavaScriptMap
    {
        $this->scriptLines[] = "delete this." . $this->pathToMap . "." . $index;
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
