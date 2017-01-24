<?php

namespace model;


interface IUsersDAO
{
    function obtainUserTO(string $phoneNumber): UserTO;

    function createUser(string $phoneNumber);
}