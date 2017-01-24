<?php
namespace model;

use mysqli;

abstract class MysqliDAO
{
    /**
     * Static link to mysqli, we try to keep all the DAO's connected with the same link to the database
     * @var null|mysqli
     */
    protected static $link = null;
    private static $instances = 0;

    function __construct() {
        if (!static::$link) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            static::$link = new mysqli(MYSQLI_HOST, MYSQLI_USER, MYSQLI_PASS, MYSQLI_DB);
        }
        self::$instances++;
    }

    function __destruct() {
        self::$instances--;
        if (self::$instances < 1 && static::$link) {
            static::$link->close();
            static::$link = null;
        }
    }
}