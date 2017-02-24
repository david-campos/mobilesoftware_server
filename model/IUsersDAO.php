<?php

namespace model;


interface IUsersDAO
{
    function obtainUserTO(string $phoneNumber): UserTO;

    function obtainUserTOById(int $id): UserTO;
    function createUser(string $phoneNumber);

    /**
     * @param array $phones
     * @param null|string $lastUpdate
     * @return UserTO[]
     */
    function getExistentUsers(array $phones, $lastUpdate): array;
}