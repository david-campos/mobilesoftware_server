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
        $gen = Strings::getConstants()['general_params'];
        $request_name = $vars[$gen['request']];
        if ($request_name === Strings::getReqName("get_appointment_types_and_reasons")) {
            $types = $this->getAppointmentTypes();
            $reasons = $this->getAppointmentReasons();
            $this->opt->printApppointmentTypesAndReasons($types, $reasons);
        } else {
            $session_manager = new SessionManager();
            $phone = $vars[$gen["phone"]];
            if ($request_name === Strings::getReqName("init_session")) {
                $session = $session_manager->login($phone);
                $this->opt->printSessionKey(
                    $session[SessionManager::KEY_KEY],
                    $session[SessionManager::KEY_ID]);
            } else {
                $sessId = $vars[$gen["session_id"]];
                $sessKey = $vars[$gen["session_key"]];
                if ($session_manager->logCheck($sessId, $sessKey, $phone)) {
                    $userTO = DAOFactory::getInstance()->obtainUsersDAO()->obtainUserTO($phone);
                    switch ($request_name) {
                        case Strings::getReqName("block_user"):
                        case Strings::getReqName("change_user_name"):
                        case Strings::getReqName("change_profile_pic"):
                        case Strings::getReqName("remove_profile_pic"):
                            (new ProfileRequestManager($userTO, $this))->processProfileRequest($vars);
                            break;
                        default:
                            (new AppointmentRequestManager($userTO, $this))->processAppointmentRequest($vars);
                            break;
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