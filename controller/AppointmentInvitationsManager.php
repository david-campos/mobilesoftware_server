<?php
/**
 * Created by PhpStorm.
 * User: David Campos R.
 * Date: 20/01/2017
 * Time: 15:56
 */

namespace controller;

use model\AppointmentTO;

class AppointmentInvitationsManager
{
    /**
     * @var AppointmentRequestManager
     */
    private $appointmentManager;
    /**
     * @var AppointmentTO
     */
    private $appointment;

    /**
     * AppointmentInvitationsManager constructor.
     * @param AppointmentRequestManager $appointmentManager
     * @param AppointmentTO $appointment
     */
    public function __construct(AppointmentTO $appointment, AppointmentRequestManager $appointmentManager) {
        $this->appointmentManager = $appointmentManager;
        $this->appointment = $appointment;
    }

    public function changeInvitationState(string $newState, string $reasonName) {
        $userId = $this->appointmentManager->getUserTO()->getId();
        foreach ($this->appointment->getInvitations() as $invitation) {
            if ($invitation->getUser() === $userId) {
                $invitation->setState($newState);
                $invitation->setReason($reasonName);
                break;
            }
        }
    }
}