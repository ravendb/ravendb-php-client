<?php

namespace RavenDB\primitives;

/**
 * Parent class for all EventArgs
 */
class EventArgs
{
    //empty by design

    public static VoidArgs $EMPTY;
}

EventArgs::$EMPTY = new VoidArgs();
