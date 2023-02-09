<?php

namespace RavenDB\Primitives;

use DateTime;
use DateTimeInterface;
use DateTimeZone;

// !status: IN PROGRESS
class NetISO8601Utils
{
//    /**
//     * ID to represent the 'GMT' string
//     */
//    private static final String GMT_ID = "GMT";
//
//    /**
//     * The GMT timezone
//     */
//    private static final TimeZone TIMEZONE_GMT = TimeZone.getTimeZone(GMT_ID);

    const ISO_FORMAT = "Y-m-d\TH:i:s.v0000\Z";

    /**
     * Format date into yyyy-MM-ddThh:mm:ss[.sss][Z|[+-]hh:mm]
     *
     * @param DateTimeInterface $date   the date to format
     * @param bool $millis true to include millis precision otherwise false
     * @param ?DateTimeZone $tz     timezone to use for the formatting (GMT will produce 'Z')
     *
     * @return string the date formatted as yyyy-MM-ddThh:mm:ss[.sss][Z|[+-]hh:mm]
     */
    public static function format(DateTimeInterface $date, bool $millis = false, ?DateTimeZone $tz = null): string
    {
        return $date->format(self::ISO_FORMAT) ;
    }

//
//    /**
//     * Parse a date from ISO-8601 formatted string. It expects a format yyyy-MM-ddThh:mm:ss[.sss][Z|[+-]hh:mm]
//     *
//     * @param date ISO string to parse in the appropriate format.
//     * @return the parsed date
//     * @throws IllegalArgumentException if the date is not in the appropriate format
//     */
//    public static Date parse(String date) {
//        if (date.startsWith("0001-01-01T00:00:00.0000000")) {
//            return new Date(0);
//        }
//
//        try {
//            int offset = 0;
//
//            // extract year
//            int year = parseInt(date, offset, offset += 4);
//            checkOffset(date, offset, '-');
//
//            // extract month
//            int month = parseInt(date, offset += 1, offset += 2);
//            checkOffset(date, offset, '-');
//
//            // extract day
//            int day = parseInt(date, offset += 1, offset += 2);
//            checkOffset(date, offset, 'T');
//
//            // extract hours, minutes, seconds and milliseconds
//            int hour = parseInt(date, offset += 1, offset += 2);
//            checkOffset(date, offset, ':');
//
//            int minutes = parseInt(date, offset += 1, offset += 2);
//            checkOffset(date, offset, ':');
//
//            int seconds = parseInt(date, offset += 1, offset += 2);
//            // milliseconds can be optional in the format
//            int milliseconds = 0; // always use 0 otherwise returned date will include millis of current time
//            if (date.charAt(offset) == '.') {
//                checkOffset(date, offset, '.');
//                milliseconds = parseInt(date, offset += 1, offset += 3);
//                for (int i = 0; i < 4; i++) {
//                    if (Character.isDigit(date.charAt(offset))) {
//                        offset++;
//                    } else {
//                        break;
//                    }
//                }
//            }
//
//            // extract timezone
//            String timezoneId;
//            if (date.length() == offset) {
//                // default to Z
//                timezoneId = GMT_ID;
//            } else {
//
//                char timezoneIndicator = date.charAt(offset);
//                switch (timezoneIndicator) {
//                    case '+':
//                    case '-':
//                        timezoneId = GMT_ID + date.substring(offset);
//                        break;
//                    case 'Z':
//                        timezoneId = GMT_ID;
//                        break;
//                    default:
//                        throw new IndexOutOfBoundsException("Invalid time zone indicator " + timezoneIndicator);
//                }
//            }
//            TimeZone timezone = TimeZone.getTimeZone(timezoneId);
//            if (!timezone.getID().equals(timezoneId)) {
//                throw new IndexOutOfBoundsException();
//            }
//
//            Calendar calendar = new GregorianCalendar(timezone);
//            calendar.setLenient(false);
//            calendar.set(Calendar.YEAR, year);
//            calendar.set(Calendar.MONTH, month - 1);
//            calendar.set(Calendar.DAY_OF_MONTH, day);
//            calendar.set(Calendar.HOUR_OF_DAY, hour);
//            calendar.set(Calendar.MINUTE, minutes);
//            calendar.set(Calendar.SECOND, seconds);
//            calendar.set(Calendar.MILLISECOND, milliseconds);
//
//            return calendar.getTime();
//        } catch (IndexOutOfBoundsException | IllegalArgumentException e) {
//    throw new IllegalArgumentException("Failed to parse date " + date, e);
//}
//    }
//
//    /**
//     * Check if the expected character exist at the given offset of the
//     *
//     * @param value    the string to check at the specified offset
//     * @param offset   the offset to look for the expected character
//     * @param expected the expected character
//     * @throws IndexOutOfBoundsException if the expected character is not found
//     */
//    private static void checkOffset(String value, int offset, char expected) throws IndexOutOfBoundsException {
//    char found = value.charAt(offset);
//        if (found != expected) {
//            throw new IndexOutOfBoundsException("Expected '" + expected + "' character but found '" + found + "'");
//        }
//    }
//
//    /**
//     * Parse an integer located between 2 given offsets in a string
//     *
//     * @param value      the string to parse
//     * @param beginIndex the start index for the integer in the string
//     * @param endIndex   the end index for the integer in the string
//     * @return the int
//     * @throws NumberFormatException if the value is not a number
//     */
//    private static int parseInt(String value, int beginIndex, int endIndex) throws NumberFormatException {
//    if (beginIndex < 0 || endIndex > value.length() || beginIndex > endIndex) {
//        throw new NumberFormatException(value);
//    }
//    // use same logic as in Integer.parseInt() but less generic we're not supporting negative values
//    int i = beginIndex;
//        int result = 0;
//        int digit;
//        if (i < endIndex) {
//            digit = Character.digit(value.charAt(i++), 10);
//            if (digit < 0) {
//                throw new NumberFormatException("Invalid number: " + value);
//            }
//            result = -digit;
//        }
//        while (i < endIndex) {
//            digit = Character.digit(value.charAt(i++), 10);
//            if (digit < 0) {
//                throw new NumberFormatException("Invalid number: " + value);
//            }
//            result *= 10;
//            result -= digit;
//        }
//        return -result;
//    }
//
//    /**
//     * Zero pad a number to a specified length
//     *
//     * @param buffer buffer to use for padding
//     * @param value  the integer value to pad if necessary.
//     * @param length the length of the string we should zero pad
//     */
//    private static void padInt(StringBuilder buffer, int value, int length) {
//    String strValue = Integer.toString(value);
//        for (int i = length - strValue.length(); i > 0; i--) {
//        buffer.append('0');
//    }
//        buffer.append(strValue);
//    }

    public static function fromString(string $datetime): DateTime
    {
        return DateTime::createFromFormat(self::ISO_FORMAT, $datetime);
    }
}
