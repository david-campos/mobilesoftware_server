<?php
/**
 * Created by PhpStorm.
 * User: ElJüsticieroMisteryo
 * Date: 20/01/2017
 * Time: 16:03
 */

namespace view;


use model\AppointmentTO;
use model\PropositionTO;
use model\UserTO;

class OutputterJSON implements Outputter
{
    private function safe_json_encode_and_print(array $array) {
        $json = json_encode($array);
        if ($json === false) {
            // Avoid echo of an empty string (invalid JSON)
            $json = json_encode(array("jsonError", json_last_error_msg()));
            if ($json === false) {
                $json = '{"jsonError": "unknown"}'; // Extrem case
            }
            http_response_code(500);
        }
        echo $json;
    }

    public function printUserTO(UserTO $userTO) {
        $this->safe_json_encode_and_print($userTO->toAssociativeArray());
    }

    /**
     * @param AppointmentTO[] $appointmentToList
     */
    public function printAppointmentList(array $appointmentToList) {
        $appointments = array();
        foreach ($appointmentToList as $app) {
            $appointments[] = $app->toAssociativeArray();
        }
        $this->safe_json_encode_and_print($appointments);
    }

    public function printAppointment(AppointmentTO $appointmentTO) {
        $this->safe_json_encode_and_print($appointmentTO->toAssociativeArray());
    }

    /**
     * @param PropositionTO[] $propositionToList
     */
    public function printPropositionList(array $propositionToList) {
        $propositions = array();
        foreach ($propositionToList as $prop) {
            $propositions[] = $prop->toAssociativeArray();
        }
        $this->safe_json_encode_and_print($propositions);
    }

    public function printProposition(PropositionTO $propositionTO) {
        $this->safe_json_encode_and_print($propositionTO->toAssociativeArray());
    }

    public function printError(string $errorString, int $errorCode) {
        http_response_code(500);
        $this->safe_json_encode_and_print(array(
            "error" => $errorString,
            "code" => $errorCode
        ));
    }

    public function printSessionKey(string $sessionKey, int $sessionId) {
        $this->safe_json_encode_and_print(array(
            "key" => $sessionKey,
            "sessionId" => $sessionId
        ));
    }

    public function printApppointmentTypesAndReasons(array $appointmentTypes, array $reasons) {
        $this->safe_json_encode_and_print(array(
            "appointmentTypes" => $appointmentTypes,
            "reasons" => $reasons
        ));
    }
}