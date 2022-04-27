<?php

namespace RavenDB\Primitives;

// @todo: implement this class - currently it cant be simply copied from java
// note: maybe the function declaration is not right at all
class EventHelper
{
    /**
     * Helper used for invoking event on list of delegates
     *
     * @param ClosureArray $delegates      Event delegates
     * @param object $sender        Event sender
     * @param ?EventArgs $event      Event to send
     *
     */
    public static function invoke(ClosureArray $delegates, object $sender, ?EventArgs $event = null): void
    {
        /* * @var Closure $delegate */
        foreach ($delegates as $delegate) {
            $delegate($sender, $event);
        }
    }

    public static function invokeActions(array $actions, object $argument): void
    {

//        for (Consumer<T> action : actions) {
//            action.accept(argument);
//        }
    }
}
