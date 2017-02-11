<?php

namespace model;


interface IUsersDAO
{
    function obtainUserTO(string $phoneNumber): UserTO;

    function createUser(string $phoneNumber);

    /**
     * @param array $phones
     * @return UserTO[]
     */
    function getExistentUsers(array $phones): array;
}