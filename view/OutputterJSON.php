<?php
/**
 * Created by PhpStorm.
 * User: David Campos R.
 * Date: 20/01/2017
 * Time: 16:03
 */

namespace view;


use model\AppointmentTO;
use model\PropositionTO;
use model\UserTO;

class OutputterJSON implements Outputter
{
    private $rustart;

    /**
     * OutputterJSON constructor.
     */
    public function __construct() {
        $this->rustart = getrusage();
        http_response_code(200);
    }

    // Script end
    private function rutime($ru, $rus, $index) {
        return ($ru["ru_$index.tv_sec"] * 1000.0 + intval($ru["ru_$index.tv_usec"] / 1000.0))
        - ($rus["ru_$index.tv_sec"] * 1000.0 + intval($rus["ru_$index.tv_usec"] / 1000.0));
    }

    private function safe_json_encode_and_print(array $array) {
        header('Content-Type: application/json;charset=utf-8');

        $ru = getrusage();
        $wrap = array('result' => $array,
            'permormance_info' => array(
                'computationTime' => $this->rutime($ru, $this->rustart, "utime"),
                'systemCallsTime' => $this->rutime($ru, $this->rustart, "stime")
            ));

        $json = json_encode($wrap);
        if ($json === false) {
            // Avoid echo of an empty string (invalid JSON)
            $json = json_encode(array("jsonError", json_last_error_msg()));
            if ($json === false) {
                $json = '{"jsonError": "unknown"}'; // Extrem case
            }
            http_response_code(418);
        }
        echo $json;
    }

    public function printUserTO(UserTO $userTO) {
        $this->safe_json_encode_and_print($userTO->toAssociativeArray(true));
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

    /**
     * @param array(blocked=>bool,user=>UserTO) $usersList
     */
    public function printUsers(array $usersList) {
        $users = array();
        foreach ($usersList as $mix) {
            $user = ($mix["user"])->toAssociativeArray(false);
            $user["blocked"] = $mix["blocked"];
            $users[] = $user;
        }
        $this->safe_json_encode_and_print($users);
    }
}