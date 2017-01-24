<?php
/**
 * Created by PhpStorm.
 * User: David Campos R.
 * Date: 20/01/2017
 * Time: 11:50
 */

namespace controller;


use exceptions\WrongRequestException;
use model\AppointmentTO;
use model\DAOFactory;
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
        $gen = Strings::getConstants()['general_params'];
        $request = $vars[$gen['request']];
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
                $appointmentId = $vars[Strings::getGenParam('appointment_id')];
                $appointment = DAOFactory::getInstance()->obtainAppointmentsDAO()->obtainAppointmentTO($appointmentId);
                if ($request === Strings::getReqName('close_appointment')) {
                    $this->closeAppointment($appointment);
                    $this->requestProcessor->getOutputter()->printAppointment($appointment);
                } else {
                    if ($request === Strings::getReqName('accept_invitation') ||
                        $request === Strings::getReqName('refuse_invitation')
                    ) {
                        $invitationsManager = new AppointmentInvitationsManager($appointment, $this);
                        $newState = ($request === Strings::getReqName('accept_invitation')) ? 'accepted' : 'refused';
                        $reasonName = $vars[Strings::getReqParam('refuse_invitation', 'param_reason')];
                        $invitationsManager->changeInvitationState($newState, $reasonName);
                        $appointment->synchronize();
                        $appointment->updateInvitationsFromBD(); // Update invitation description
                        $this->requestProcessor->getOutputter()->printAppointment($appointment);
                    } else {
                        $proposalsManager = new AppointmentProposalsManager($appointment, $this);
                        switch ($request) {
                            case Strings::getReqName('accept_proposition'):
                                $placeName = $vars[Strings::getReqParam('accept_proposition', 'param_place')];
                                $timestamp = strtotime($vars[Strings::getReqParam('accept_proposition', 'param_timestamp')]);
                                $proposalsManager->changeCurrentProposal($placeName, $timestamp);
                                $this->requestProcessor->getOutputter()->printAppointment($appointment);
                                break;
                            case Strings::getReqName('get_appointment_propositions'):
                                $list = $proposalsManager->getProposals();
                                $this->requestProcessor->getOutputter()->printPropositionList($list);
                                break;
                            case Strings::getReqName('create_proposition'):
                                $placeName = $vars[Strings::getReqParam('create_proposition', 'param_place')];
                                $timestamp = strtotime($vars[Strings::getReqParam('create_proposition', 'param_timestamp')]);
                                $placeLon = $vars[Strings::getReqParam('create_proposition', 'param_lon')];
                                $placeLat = $vars[Strings::getReqParam('create_proposition', 'param_lat')];
                                $reasonName = $vars[Strings::getReqParam('create_proposition', 'param_reason')];
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
        $timestamp = strtotime($vars[Strings::getReqParam('create_appointment', 'param_timestamp')]);
        $place = $vars[Strings::getReqParam('create_appointment', 'param_place')];
        $coords = array(
            "lat" => $vars[Strings::getReqParam('create_appointment', 'param_coords_la')],
            "lon" => $vars[Strings::getReqParam('create_appointment', 'param_coords_lo')]
        );
        $reason = $vars[Strings::getReqParam('create_appointment', 'param_reason')];
        $name = $vars[Strings::getReqParam('create_appointment', 'param_name')];
        $description = $vars[Strings::getReqParam('create_appointment', 'param_description')];
        $closed = $vars[Strings::getReqParam('create_appointment', 'param_closed')];
        $type = $vars[Strings::getReqParam('create_appointment', 'param_type')];
        $users = preg_split(
            '/,/',
            $vars[Strings::getReqParam('create_appointment', 'param_invited_users')]);

        $initial_proposition = DAOFactory::getInstance()->obtainPropositionsDAO()->createProposition(
            0, $timestamp, $place, $coords, $reason, $this->user->getId());

        return DAOFactory::getInstance()->obtainAppointmentsDAO()->createAppointment(
            $name, $description, $closed, $type, $this->user->getId(), $users, $initial_proposition);
    }

    private function closeAppointment(AppointmentTO $appointment): void {
        if (!$appointment->isClosed()) {
            $appointment->close();
            $appointment->synchronize();
        }
    }
}