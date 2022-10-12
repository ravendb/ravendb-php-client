<?php

namespace RavenDB\Utils;

use RavenDB\Type\Duration;

class TimeUtils
{
//     private static Duration parseMiddlePart(String input) {
//        String[] tokens = input.split(":");
//        int hours = Integer.parseInt(tokens[0]);
//        int minutes = Integer.parseInt(tokens[1]);
//        int seconds = Integer.parseInt(tokens[2]);
//
//        if (tokens.length != 3) {
//            throw new IllegalArgumentException("Unexpected Duration format: "+ input);
//        }
//
//        return Duration.ofHours(hours).plusMinutes(minutes).plusSeconds(seconds);
//    }
//
//    public static Duration timeSpanToDuration(String text) {
//        boolean hasDays = text.matches("^\\d+\\..*");
//        boolean hasMillis = text.matches(".*\\.\\d+");
//
//        if (hasDays && hasMillis) {
//            String[] tokens = text.split("\\.");
//
//            int days = Integer.parseInt(tokens[0]);
//            int millis = Integer.parseInt(tokens[2]);
//            return parseMiddlePart(tokens[1]).plusDays(days).plusMillis(millis);
//        } else if (hasDays) {
//            String[] tokens = text.split("\\.");
//            int days = Integer.parseInt(tokens[0]);
//            return parseMiddlePart(tokens[1]).plusDays(days);
//        } else if (hasMillis) {
//            String[] tokens = text.split("\\.");
//            String fractionString = tokens[1];
//            fractionString = StringUtils.rightPad(fractionString, 7, '0');
//            long value = Long.parseLong(fractionString);
//
//            value *= 100;
//
//            return parseMiddlePart(tokens[0]).plusNanos(value);
//        } else {
//            return parseMiddlePart(text);
//        }
//
//    }

    static public function durationToTimeSpan(Duration $duration): string
    {
        return $duration->format();
//        $result = "";
//
//        if ($duration->days) {
//            $result .= $duration->format("a.");
//        }
//
//        $result .=  $duration->format("H:I:S");
//
//        if ($duration->f) {
//            $result .= $duration->format(".F");
//        }
//
//        return $result;
    }
}
