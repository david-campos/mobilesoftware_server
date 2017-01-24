<?php
/**
 * Created by PhpStorm.
 * User: David Campos R.
 * Date: 20/01/2017
 * Time: 11:50
 */

namespace controller;

require_once dirname(__FILE__) . '/controller_exceptions/WrongRequestException.php';
require_once dirname(__FILE__) . '/AppointmentInvitationsManager.php';
require_once dirname(__FILE__) . '/AppointmentProposalsManager.php';

use DateTime;
use exceptions\WrongRequestException;
use model\AppointmentTO;
use model\DAOFactory;
use model\PropositionTO;
use model\UserTO;

class AppointmentRequestManager
{
    /**
     * @var UserTO
     */
    private $user;
    /**
     * @var RequestProcessor
     */
    private $requestProcessor;

    /**
     * AppointmentRequestManager constructor.
     * @param UserTO $user
     * @param $requestProcessor
     */
    public function __construct(UserTO $user, RequestProcessor $requestProcessor) {
        $this->user = $user;
        $this->requestProcessor = $requestProcessor;
    }

    /**
     * @return UserTO
     */
    public function getUserTO(): UserTO {
        return $this->user;
    }

    public function processAppointmentRequest(array $vars) {
        $request = Strings::getGenParamValueIn('request', $vars);
        switch ($request) {
            case Strings::getReqName('get_user_appointments'):
                $this->requestProcessor->getOutputter()->printAppointmentList(
                    DAOFactory::getInstance()->obtainAppointmentsDAO()->obtainAppointmentsOfUser($this->user->getId()));
                break;
            case Strings::getReqName('create_appointment'):
                $id = $this->createAppointment($vars);
                $this->requestProcessor->getOutputter()->printAppointment(
                    DAOFactory::getInstance()->obtainAppointmentsDAO()->obtainAppointmentTO($id));
                break;
            default:
                $appointmentId = Strings::getGenParamValueIn('appointment_id', $vars);
                $appointment = DAOFactory::getInstance()->obtainAppointmentsDAO()->obtainAppointmentTO($appointmentId);
                if ($request === Strings::getReqName('close_appointment')) {
                    $this->closeAppointment($appointment);
                    $this->requestProcessor->getOutputter()->printAppointment($appointment);
                } else if ($request === Strings::getReqName('open_appointment')) {
                    $this->openAppointment($appointment);
                    $this->requestProcessor->getOutputter()->printAppointment($appointment);
                } else {
                    if ($request === Strings::getReqName('accept_invitation') ||
                        $request === Strings::getReqName('refuse_invitation')
                    ) {
                        $invitationsManager = new AppointmentInvitationsManager($appointment, $this);
                        $newState = ($request === Strings::getReqName('accept_invitation')) ? 'accepted' : 'refused';
                        $reasonName = Strings::getParamValueIn('refuse_invitation', 'param_reason', $vars);
                        $invitationsManager->changeInvitationState($newState, $reasonName);
                        $appointment->synchronize();
                        $appointment->updateInvitationsFromBD(); // Update invitation description
                        $this->requestProcessor->getOutputter()->printAppointment($appointment);
                    } else {
                        $proposalsManager = new AppointmentProposalsManager($appointment, $this);
                        switch ($request) {
                            case Strings::getReqName('accept_proposition'):
                                $placeName = Strings::getParamValueIn('accept_proposition', 'param_place', $vars);
                                $timestamp = DateTime::createFromFormat('Y-m-d H:i:s', Strings::getParamValueIn('accept_proposition', 'param_timestamp', $vars))->getTimestamp();
                                $proposalsManager->changeCurrentProposal($placeName, $timestamp);
                                $this->requestProcessor->getOutputter()->printAppointment($appointment);
                                break;
                            case Strings::getReqName('get_appointment_propositions'):
                                $list = $proposalsManager->getProposals();
                                $this->requestProcessor->getOutputter()->printPropositionList($list);
                                break;
                            case Strings::getReqName('create_proposition'):
                                $placeName = Strings::getParamValueIn('create_proposition', 'param_place', $vars);
                                $timestamp = DateTime::createFromFormat('Y-m-d H:i:s', Strings::getParamValueIn('create_proposition', 'param_timestamp', $vars))->getTimestamp();
                                $placeLon = Strings::getParamValueIn('create_proposition', 'param_lon', $vars);
                                $placeLat = Strings::getParamValueIn('create_proposition', 'param_lat', $vars);
                                $reasonName = Strings::getParamValueIn('create_proposition', 'param_reason', $vars);
                                $proposition = $proposalsManager->createReplaceProposal($timestamp, $placeName, $placeLon, $placeLat, $reasonName);
                                $this->requestProcessor->getOutputter()->printProposition($proposition);
                                break;
                            default:
                                throw new WrongRequestException("The request $request is an unknown one.");
                        }
                    }
                }
                break;
        }
    }

    private function createAppointment(array $vars): int {
        $timestamp = DateTime::createFromFormat('Y-m-d H:i:s', Strings::getParamValueIn('create_appointment', 'param_timestamp', $vars))->getTimestamp();
        $place = Strings::getParamValueIn('create_appointment', 'param_place', $vars);
        $coordlat = (double)Strings::getParamValueIn('create_appointment', 'param_coords_lat', $vars);
        $coordlon = (double)Strings::getParamValueIn('create_appointment', 'param_coords_lon', $vars);
        $reason = Strings::getParamValueIn('create_appointment', 'param_reason', $vars);
        $name = Strings::getParamValueIn('create_appointment', 'param_name', $vars);
        $description = Strings::getParamValueIn('create_appointment', 'param_description', $vars);
        $closed = Strings::getParamValueIn('create_appointment', 'param_closed', $vars);
        $type = Strings::getParamValueIn('create_appointment', 'param_type', $vars);
        $users = preg_split(
            '/,/',
            Strings::getParamValueIn('create_appointment', 'param_invited_users', $vars));

        $initial_proposition = new PropositionTO(0, $timestamp, $coordlat, $coordlon, $place, $reason, false, $this->user->getId());

        return DAOFactory::getInstance()->obtainAppointmentsDAO()->createAppointment(
            $name, $description, $closed, $type, $this->user->getId(), $users, $initial_proposition);
    }

    private function closeAppointment(AppointmentTO $appointment) {
        if (!$appointment->isClosed()) {
            $appointment->close();
            $appointment->synchronize();
        }
    }

    private function openAppointment(AppointmentTO $appointment) {
        if ($appointment->isClosed()) {
            $appointment->open();
            $appointment->synchronize();
        }
    }
}