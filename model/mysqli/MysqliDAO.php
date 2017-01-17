<?php
namespace model;

use mysqli;

abstract class MysqliDAO
{
    protected $link = null;

    function __construct() {
        $this->link = new mysqli(MYSQLI_HOST, MYSQLI_USER, MYSQLI_PASS, MYSQLI_DB);
    }

    function close() {
        if ($this->link) $this->link->close();
    }
}