<?php
/**
 * Created by PhpStorm.
 * User: David Campos R.
 * Date: 17/01/2017
 * Time: 18:28
 */

namespace controller;

// Require the view
require_once dirname(__FILE__) . '/../view/ViewFacade.php';
// Require the model
require_once dirname(__FILE__) . '/../model/DAOFactory.php';
// Require controller stuff
require_once dirname(__FILE__) . '/AppointmentRequestManager.php';
require_once dirname(__FILE__) . '/ProfileRequestManager.php';
require_once dirname(__FILE__) . '/SessionManager.php';
require_once dirname(__FILE__) . '/Strings.php';

use exceptions\IllegalArgumentException;
use model\DAOFactory;
use view\ViewFacade;

class RequestProcessor
{
    private $opt;

    function __construct() {
        $this->opt = (new ViewFacade())->getOutputter();
    }

    public function getOutputter() {
        return $this->opt;
    }

    public function processRequest(array $vars) {
        $request_identifier = Strings::getGenParamValueIn('request', $vars);
        if ($request_identifier === Strings::getReqIdentifier("get_appointment_types_and_reasons")) {
            $types = $this->getAppointmentTypes();
            $reasons = $this->getAppointmentReasons();
            $this->opt->printApppointmentTypesAndReasons($types, $reasons);
        } else {
            $session_manager = new SessionManager();
            $phone = Strings::getGenParamValueIn('phone', $vars);
            if ($request_identifier === Strings::getReqIdentifier("init_session")) {
                $session = $session_manager->login($phone);
                $this->opt->printSessionKey(
                    $session[SessionManager::KEY_KEY],
                    $session[SessionManager::KEY_ID]);
            } else {
                $sessId = Strings::getGenParamValueIn('session_id', $vars);
                $sessKey = Strings::getGenParamValueIn('session_key', $vars);
                if ($session_manager->logCheck($sessId, $sessKey, $phone)) {
                    $userTO = DAOFactory::getInstance()->obtainUsersDAO()->obtainUserTO($phone);
                    switch (Strings::getReqProcessorClass($request_identifier)) {
                        case 'ProfileRequestManager':
                            (new ProfileRequestManager($userTO, $this))->processProfileRequest($vars);
                            break;
                        case 'AppointmentInvitationsManager':
                        case 'AppointmentProposalsManager':
                        case 'AppointmentRequestManager':
                            (new AppointmentRequestManager($userTO, $this))->processAppointmentRequest($vars);
                            break;
                        case null:
                            throw new IllegalArgumentException("The request '$request_identifier' doesn't exist");
                            break;
                        default:
                            throw new \Exception("Req processor for '$request_identifier' not added in RequestProcessor yet");
                    }
                } else {
                    $this->opt->printError("Session check failed", 100);
                }
            }
        }
    }

    private function getAppointmentTypes(): array {
        return DAOFactory::getInstance()->obtainTypesAndReasonsDAO()->getTypes();
    }

    private function getAppointmentReasons(): array {
        return DAOFactory::getInstance()->obtainTypesAndReasonsDAO()->getReasons();
    }
}