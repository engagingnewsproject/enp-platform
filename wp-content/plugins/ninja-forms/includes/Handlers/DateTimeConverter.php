<?php
namespace NinjaForms\Includes\Handlers;

/**
 * Converts timestamps between formats, cognizant of WordPress settings
 *
 * This class is aware of the WordPress timezone settings and can convert
 * stringed times into integer timestamps and vice-versa, relieving all other
 * classes of this responsibility. 
 */
class DateTimeConverter
{

    /**
     * Convert datetime string into epoch integer adjusted for WP timezone
     *
     * @param string $dateTime
     * @return integer
     */
    public static function localizeDateTimeStringIntoEpoch(string $dateTimeString): int
    {
        if(''==$dateTimeString){
            $dateTimeString = '1970-01-01 00:00:00';
        }
        
        $timezone = self::getWpTimezoneSetting();
        
        $dateTimeObject = new \DateTime( $dateTimeString, $timezone );
        
        $return = $dateTimeObject->getTimestamp();
        
        return $return;
    }

    /**
     * Convert timestamp into local time adjusted for WP timezone
     *
     * @param integer $epochTimestamp
     * @param string|null $format
     * @return string
     */
    public static function localizeEpochIntoString(int $epochTimestamp, ?string $format = 'Y-m-d H:i:s'): string
    {
        $timezone = self::getWpTimezoneSetting();

        $dateTime = new \DateTime();
        $dateTime->setTimezone($timezone);
        $dateTime->setTimestamp($epochTimestamp);

        $return = $dateTime->format($format);

        return $return;
    }

    /**
     *  Returns the blog timezone
     *
     * Gets timezone settings from the db. If a timezone identifier is used just
     * turns it into a DateTimeZone. If an offset is used, it tries to find a
     * suitable timezone. If all else fails it uses UTC.
     *
     * credit:
     * https://wordpress.stackexchange.com/questions/198435/how-to-convert-datetime-to-display-time-based-on-wordpress-timezone-setting#198453
     * @return \DateTimeZone The blog timezone
     */
    public static function getWpTimezoneSetting():\DateTimeZone
    {
        $tzstring = \get_option('timezone_string');
        $offset   = \get_option('gmt_offset');

        //Manual offset...
        //@see http://us.php.net/manual/en/timezones.others.php
        //@see https://bugs.php.net/bug.php?id=45543
        //@see https://bugs.php.net/bug.php?id=45528
        //IANA timezone database that provides PHP's timezone support uses POSIX (i.e. reversed) style signs
        if (empty($tzstring) && 0 != $offset && floor($offset) == $offset) {
            $offset_st = $offset > 0 ? "-$offset" : '+' . absint($offset);
            $tzstring  = 'Etc/GMT' . $offset_st;
        }

        //Issue with the timezone selected, set to 'UTC'
        if (empty($tzstring)) {
            $tzstring = 'UTC';
        }

        $timezone = new \DateTimeZone($tzstring);
        return $timezone;
    }
}
