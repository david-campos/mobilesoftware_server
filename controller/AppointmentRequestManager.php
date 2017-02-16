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
use exceptions\IllegalArgumentException;
use exceptions\RequiredParameterException;
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

        $appointment = null;
        // Only this two requests doesn't need an appointment
        if ($request !== Strings::getReqIdentifier('get_user_appointments') &&
            $request !== Strings::getReqIdentifier('create_appointment')
        ) {

            $appointmentId = Strings::getGenParamValueIn('appointment_id', $vars);
            $appointment = DAOFactory::getInstance()->obtainAppointmentsDAO()->obtainAppointmentTO($appointmentId);
        }

        switch (Strings::getReqProcessorClass($request)) {
            // Appointments for this class -----------------------------------------------------------------------------
            case 'AppointmentRequestManager':
                switch ($request) {
                    case Strings::getReqIdentifier('get_user_appointments'):
                        $this->requestProcessor->getOutputter()->printAppointmentList(
                            DAOFactory::getInstance()->obtainAppointmentsDAO()->obtainAppointmentsOfUser($this->user->getId()));
                        break;
                    case Strings::getReqIdentifier('create_appointment'):
                        $id = $this->createAppointment($vars);
                        $this->requestProcessor->getOutputter()->printAppointment(
                            DAOFactory::getInstance()->obtainAppointmentsDAO()->obtainAppointmentTO($id));
                        break;
                    case Strings::getReqIdentifier('close_appointment'):
                        $this->closeAppointment($appointment);
                        $this->requestProcessor->getOutputter()->printAppointment($appointment);
                        break;
                    case Strings::getReqIdentifier('open_appointment'):
                        $this->openAppointment($appointment);
                        $this->requestProcessor->getOutputter()->printAppointment($appointment);
                        break;
                    default:
                        throw new \Exception("AppointmentRequestManager implementation for '$request' not done yet");
                }
                break;

            // Appointments for invitations manager --------------------------------------------------------------------
            case 'AppointmentInvitationsManager':
                switch ($request) {
                    case Strings::getReqIdentifier('accept_invitation'):
                    case Strings::getReqIdentifier('refuse_invitation'):
                        $invitationsManager = new AppointmentInvitationsManager($appointment, $this);
                        $newState = ($request === Strings::getReqIdentifier('accept_invitation')) ? 'accepted' : 'refused';
                        $reasonName = Strings::getParamValueIn('refuse_invitation', 'param_reason', $vars);
                        $invitationsManager->changeInvitationState($newState, $reasonName);
                        $appointment->synchronize();
                        $appointment->updateInvitationsFromBD(); // Update invitation description
                        $this->requestProcessor->getOutputter()->printAppointment($appointment);
                        break;
                    default:
                        throw new \Exception("AppointmentInvitationsManager implementation for '$request' not done yet");
                }
                break;

            // Appointments for proposals manager ----------------------------------------------------------------------
            case 'AppointmentProposalsManager': {
                $proposalsManager = new AppointmentProposalsManager($appointment, $this);
                switch ($request) {
                    case Strings::getReqIdentifier('accept_proposition'):
                        $placeName = Strings::getParamValueIn('accept_proposition', 'param_place', $vars);
                        $dateTime = DateTime::createFromFormat('Y-m-d H:i:s',
                            Strings::getParamValueIn('accept_proposition', 'param_timestamp', $vars), new \DateTimeZone('UTC'));
                        if ($dateTime === false) {
                            throw new \Exception("Timestamp has incorrect format");
                        }
                        $timestamp = $dateTime->getTimestamp();
                        $proposalsManager->changeCurrentProposal($placeName, $timestamp);
                        $this->requestProcessor->getOutputter()->printAppointment($appointment);
                        break;
                    case Strings::getReqIdentifier('get_appointment_propositions'):
                        $list = $proposalsManager->getProposals();
                        $this->requestProcessor->getOutputter()->printPropositionList($list);
                        break;
                    case Strings::getReqIdentifier('create_proposition'):
                        $placeName = Strings::getParamValueIn('create_proposition', 'param_place', $vars);
                        $dateTime = DateTime::createFromFormat('Y-m-d H:i:s',
                            Strings::getParamValueIn('create_proposition', 'param_timestamp', $vars), new \DateTimeZone('UTC'));
                        if ($dateTime === false) {
                            throw new \Exception("Timestamp has incorrect format");
                        }
                        $timestamp = $dateTime->getTimestamp();
                        $placeLon = Strings::getParamValueIn('create_proposition', 'param_lon', $vars);
                        $placeLat = Strings::getParamValueIn('create_proposition', 'param_lat', $vars);
                        $reasonName = Strings::getParamValueIn('create_proposition', 'param_reason', $vars);
                        $proposition = $proposalsManager->createReplaceProposal($timestamp, $placeName, $placeLon, $placeLat, $reasonName);
                        $this->requestProcessor->getOutputter()->printProposition($proposition);
                        break;
                    default:
                        throw new \Exception("AppointmentProposalsManager implementation for '$request' not done yet");
                }
                break;
            }

            // Unknown requests ----------------------------------------------------------------------------------------
            default:
                throw new IllegalArgumentException(
                    "The request '$request' is not managed by the AppointmentRequestManager or delegated ones.");
        }
    }

    private function createAppointment(array $vars): int {
        $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', Strings::getParamValueIn('create_appointment', 'param_timestamp', $vars),
            new \DateTimeZone('UTC'));

        if ($dateTime === false) {
            throw new \Exception("Parameter timestamp has incorrect format");
        }
        $timestamp = $dateTime->getTimestamp();

        $place = Strings::getParamValueIn('create_appointment', 'param_place', $vars);
        $coordlat = (double)Strings::getParamValueIn('create_appointment', 'param_coords_lat', $vars);
        $coordlon = (double)Strings::getParamValueIn('create_appointment', 'param_coords_lon', $vars);
        try {
            $reason = Strings::getParamValueIn('create_appointment', 'param_reason', $vars);
        } catch (RequiredParameterException $e) {
            $reason = null;
        }
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