<?php
/**
 * Created by PhpStorm.
 * User: David Campos R.
 * Date: 20/01/2017
 * Time: 15:56
 */

namespace controller;

require_once dirname(__FILE__) . '/controller_exceptions/PropositionNotFoundException.php';

use exceptions\PropositionNotFoundException;
use model\AppointmentTO;
use model\DAOFactory;
use model\PropositionTO;

class AppointmentProposalsManager
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

    public function changeCurrentProposal($placeName, $timestamp): void {
        $proposals = $this->getProposals();
        foreach ($proposals as $prop) {
            if ($prop->getPlaceName() === $placeName && $prop->getTimestamp() === $timestamp) {
                $this->appointment->setCurrentProposition($prop);
                return;
            }
        }
        // If we get here, the proposition was not found
        throw new PropositionNotFoundException('Not a single proposition for the appointment has the given place name and timestamp');
    }

    /**
     * @return PropositionTO[]
     */
    public function getProposals() {
        return DAOFactory::getInstance()->obtainPropositionsDAO()
            ->obtainPropositionsForAppointment($this->appointment->getId());
    }

    public function createReplaceProposal($timestamp, $placeName, $placeLon, $placeLat, $reasonName) {
        $proposals = $this->getProposals();
        foreach ($proposals as $prop) {
            if ($prop->getProposer() === $this->appointmentManager->getUserTO()->getId()) {
                DAOFactory::getInstance()->obtainPropositionsDAO()->deleteProposition($prop);
                break;
            }
        }
        $coords = array('lat' => $placeLat, 'lon' => $placeLon);
        DAOFactory::getInstance()->obtainPropositionsDAO()->createProposition(
            $this->appointment->getId(), $timestamp, $placeName, $coords, $reasonName, $this->appointmentManager->getUserTO()->getId());
    }
}