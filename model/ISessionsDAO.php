<?php
/**
 * Created by PhpStorm.
 * User: David Campos R.
 * Date: 17/01/2017
 * Time: 19:07
 */

namespace model;


interface ISessionsDAO
{
    public function insertSession(string $phone, string $key): int;

    public function getSessionKey(int $id, string $phone): string;

    public function closeSession(int $id): void;
}