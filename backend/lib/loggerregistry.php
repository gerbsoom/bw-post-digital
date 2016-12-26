<?php
/**
 * This file is part of bw-post-digital.
 * Please check the file LICENSE.md for information about the license.
 *
 * @copyright Markus Riegert 2016
 * @author Markus Riegert <desmodul@drow-land.de>
 */

class LoggerRegistry
{
    /** @var Monolog\Logger[]  */
    private static $logger = array();
    private static $streamHandler;

    public static function createInstance($_logFile="global.log", $_debugLevel=Monolog\Logger::DEBUG)
    {
        self::$logger["boot"] = new Monolog\Logger("boot");
        self::$streamHandler = new Monolog\Handler\StreamHandler($_logFile, $_debugLevel);
        self::$logger["boot"]->pushHandler(self::$streamHandler);
    }

    /**
     * @param $_for
     * @return \Monolog\Logger
     */
    public static function getLogger($_for)
    {
        if (is_object($_for))
        {
            $channel = get_class($_for);
        }
        else $channel = $_for;
        if (!isset(self::$logger[$channel]))
        {
            self::$logger[$channel] = new Monolog\Logger($channel);
            self::$logger[$channel]->pushHandler(self::$streamHandler);
        }
        return self::$logger[$channel];
    }

}