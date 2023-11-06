<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Extensions\EntityMapper;
use RavenDB\Type\ObjectMap;

/**
 * An advanced patch request for a specified document (using JavaScript)
 */

class PatchRequest
{
    /** @SerializedName ("Script") */
    private ?string $script = null;

    /** @SerializedName ("Values") */
    private ?ObjectMap $values = null;

    /**
     * JavaScript function to use to patch a document
     * @return ?string Patch script
     */
    public function getScript(): ?string
    {
        return $this->script;
    }

    /**
     * JavaScript functions to use to patch a document
     * @param ?string $script Sets the value
     */
    public function setScript(?string $script): void
    {
        $this->script = $script;
    }

    /**
     * Additional arguments passed to JavaScript function from Script.
     * @return ObjectMap additional arguments
     */
    public function getValues(): ObjectMap
    {
        return $this->values;
    }

    /**
     * Additional arguments passed to JavaScript function from Script.
     * @param ObjectMap|array $values Sets patch arguments
     */
    public function setValues($values): void
    {
        if ($values instanceof ObjectMap) {
            $this->values = $values;
            return;
        }

        $this->values = ObjectMap::fromArray($values);
    }

    public function __construct()
    {
        $this->values = new ObjectMap();
    }

    public static function forScript(?string $script): PatchRequest
    {
        $request = new PatchRequest();
        $request->setScript($script);
        return $request;
    }

    public function serialize(EntityMapper $entityMapper): array
    {
        $data = [];
        $data['Script'] = $this->script;
        $data['Values'] = $this->values->isNotEmpty() ? $entityMapper->normalize($this->values) : null;
        return $data;
    }
}
