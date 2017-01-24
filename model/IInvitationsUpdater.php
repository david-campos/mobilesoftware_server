<?php
/**
 * Created by PhpStorm.
 * User: ElJüsticieroMisteryo
 * Date: 24/01/2017
 * Time: 13:05
 */

namespace model;


interface IInvitationsUpdater
{
    public function loadInvitationsFromBD(AppointmentTO $appointment): void;
}