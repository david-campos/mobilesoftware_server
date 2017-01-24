<?php
/**
 * Created by PhpStorm.
 * User: ElJüsticieroMisteryo
 * Date: 14/01/2017
 * Time: 15:24
 */

namespace model;


interface IAppointmentsDAO
{
    function obtainAppointmentTO(int $id): AppointmentTO;

    function obtainAppointmentsOfUser(int $userId): array;
    function createAppointment(string $name, string $description, bool $closed, string $typeName, int $creatorId,
                               array $invitedUsers, PropositionTO $initialProposition): int;
}