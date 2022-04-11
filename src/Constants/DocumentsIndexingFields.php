<?php

namespace RavenDB\Constants;

class DocumentsIndexingFields
{
    public const DOCUMENT_ID_FIELD_NAME = "id()";
    public const SOURCE_DOCUMENT_ID_FIELD_NAME = "sourceDocId()";
    public const REDUCE_KEY_HASH_FIELD_NAME = "hash(key())";
    public const REDUCE_KEY_KEY_VALUE_FIELD_NAME = "key()";
    public const VALUE_FIELD_NAME = "value()";
    public const ALL_FIELDS = "__all_fields";
    public const SPATIAL_SHAPE_FIELD_NAME = "spatial(shape)";
    //TBD 4.1 public const CUSTOM_SORT_FIELD_NAME = "__customSort";
}
