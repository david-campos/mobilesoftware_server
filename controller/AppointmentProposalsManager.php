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

    public function changeCurrentProposal($placeName, $timestamp) {
        $proposals = $this->getProposals();
        foreach ($proposals as $prop) {
            if ($prop->getPlaceName() === $placeName && $prop->getTimestamp() === $timestamp) {
                $this->appointment->setCurrentProposition($prop);
                $this->appointment->synchronize();
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

    public function createReplaceProposal($timestamp, $placeName, $placeLon, $placeLat, $reasonName): PropositionTO {
        $proposals = $this->getProposals();
        $proposalToErase = null;
        foreach ($proposals as $prop) {
            if ($prop->getProposer() === $this->appointmentManager->getUserTO()->getId()) {
                $proposalToErase = $prop;
                break;
            }
        }
        $coords = array('lat' => $placeLat, 'lon' => $placeLon);
        $newProp = DAOFactory::getInstance()->obtainPropositionsDAO()->createProposition(
            $this->appointment->getId(), $timestamp, $placeName, $coords, $reasonName, $this->appointmentManager->getUserTO()->getId());

        // If it is the current proposal then exchange them first
        if ($proposalToErase) {
            if ($this->appointment->getCurrentProposition()->getTimestamp() === $proposalToErase->getTimestamp() &&
                $this->appointment->getCurrentProposition()->getPlaceName() === $proposalToErase->getPlaceName()
            ) {
                $this->appointment->setCurrentProposition($newProp);
                $this->appointment->synchronize();
            }
            DAOFactory::getInstance()->obtainPropositionsDAO()->deleteProposition($proposalToErase);
        }

        return $newProp;
    }
}