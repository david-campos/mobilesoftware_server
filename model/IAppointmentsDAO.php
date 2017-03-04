<?php
/**
 * Created by PhpStorm.
 * User: David Campos R.
 * Date: 14/01/2017
 * Time: 15:24
 */

namespace model;


interface IAppointmentsDAO
{
    function obtainAppointmentTO(int $id): AppointmentTO;

    /**
     * @param int $userId
     * @param string|null $last_update
     * @return array
     */
    function obtainAppointmentsOfUser(int $userId, $last_update): array;
    function createAppointment(string $name, string $description, bool $closed, string $typeName, int $creatorId,
                               array $invitedUsers, PropositionTO $initialProposition): int;
}