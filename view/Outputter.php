<?php
/**
 * Created by PhpStorm.
 * User: David Campos R.
 * Date: 20/01/2017
 * Time: 15:57
 */

namespace view;


use model\AppointmentTO;
use model\PropositionTO;
use model\UserTO;

interface Outputter
{
    public function printUserTO(UserTO $userTO);

    public function printAppointmentList(array $appointmentToList);

    public function printAppointment(AppointmentTO $appointmentTO);

    public function printPropositionList(array $propositionToList);

    public function printProposition(PropositionTO $propositionTO);

    public function printError(string $errorString, int $errorCode);

    public function printSessionKey(string $sessionKey, int $sessionId);

    public function printApppointmentTypesAndReasons(array $appointmentTypes, array $reasons);
}