<?php

namespace RavenDB\primitives;

// @todo: implement this class - currently it cant be simply copied from java
// note: maybe the function declaration is not right at all
class EventHelper
{
    /**
     * Helper used for invoking event on list of delegates
     * @param array $delegates      Event delegates
     * @param object $sender        Event sender
     * @param EventArgs $eventArgs  Event to send
     */
    public static function invoke(array $delegates, object $sender, EventArgs $eventArgs): void
    {
//        for (EventHandler<T> delegate : delegates) {
//            delegate.handle(sender, event);
//        }
    }

    public static function invokeActions(array $actions, object $argument): void
    {
//        for (Consumer<T> action : actions) {
//            action.accept(argument);
//        }
    }
}
