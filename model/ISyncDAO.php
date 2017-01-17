<?php
namespace model;

require_once dirname(__FILE__) . '/IUsersDAO.php';

interface ISyncDAO
{
    function syncTO($TO): void;
}