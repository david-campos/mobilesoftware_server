<?php
/**
 * Created by PhpStorm.
 * User: ElJüsticieroMisteryo
 * Date: 20/01/2017
 * Time: 15:54
 */

namespace controller;


use model\DAOFactory;

class SessionManager
{
    public static const KEY_KEY = "key";
    public static const KEY_ID = "id";

    public function login(string $phone): array {
        DAOFactory::getInstance()->obtainSessionsDAO()->createNewSession($phone);
    }

    public function logCheck(int $id, string $key, string $phone): bool {
        return DAOFactory::getInstance()->obtainSessionsDAO()->checkSessionKey($key, $id, $phone);
    }
}