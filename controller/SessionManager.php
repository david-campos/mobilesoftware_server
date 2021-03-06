<?php
/**
 * Created by PhpStorm.
 * User: David Campos R.
 * Date: 20/01/2017
 * Time: 15:54
 */

namespace controller;

use exceptions\UnableToGetTOException;
use model\DAOFactory;

class SessionManager
{
    const KEY_KEY = "key";
    const KEY_ID = "id";

    public function login(string $phone): array {
        try {
            DAOFactory::getInstance()->obtainUsersDAO()->obtainUserTO($phone);
        } catch (UnableToGetTOException $ex) {
            DAOFactory::getInstance()->obtainUsersDAO()->createUser($phone);
        }
        return DAOFactory::getInstance()->obtainSessionsDAO()->createNewSession($phone);
    }

    public function logCheck(int $id, string $key, string $phone): bool {
        return DAOFactory::getInstance()->obtainSessionsDAO()->checkSessionKey($key, $id, $phone);
    }
}