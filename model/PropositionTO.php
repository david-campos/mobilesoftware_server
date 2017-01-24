<?php
/**
 * Created by PhpStorm.
 * User: ElJüsticieroMisteryo
 * Date: 14/01/2017
 * Time: 15:47
 */

namespace model;


class PropositionTO
{
    private $time;
    private $coordinates;
    private $placeName;
    private $reasonName;
    private $reasonDescription;
    private $proposer;
    private $appointment;

    /**
     * PropositionTO constructor.
     * @param int $appointment
     * @param int $time
     * @param float $coordLat
     * @param float $coordLon
     * @param string $placeName
     * @param string $reasonName
     * @param string $reasonDescription
     * @param int $proposer
     * @internal param $coordinates
     */
    public function __construct(int $appointment, int $time, double $coordLat, double $coordLon, string $placeName, string $reasonName,
                                string $reasonDescription, int $proposer) {
        $this->time = $time;
        $this->coordinates = array('lat' => $coordLat, 'lon' => $coordLon);
        $this->placeName = $placeName;
        $this->reasonName = $reasonName;
        $this->reasonDescription = $reasonDescription;
        $this->proposer = $proposer;
        $this->appointment = $appointment;
    }

    public function toAssociativeArray(): array {
        return array(
            "time" => $this->getTimestamp(),
            "coordinates" => $this->getCoordinates(),
            "placeName" => $this->getPlaceName(),
            "reasonName" => $this->getReasonName(),
            "reasonDescription" => $this->getReasonDescription(),
            "proposer" => $this->getProposer(),
            "appointment" => $this->getAppointmentId()
        );
    }

    public function getTimestamp(): int {
        return $this->time;
    }

    public function getCoordinates(): array {
        return $this->coordinates;
    }

    public function getPlaceName(): string {
        return $this->placeName;
    }

    public function getReasonName(): string {
        return $this->reasonName;
    }

    public function getReasonDescription(): string {
        return $this->reasonDescription;
    }

    public function getProposer(): int {
        return $this->proposer;
    }

    public function getAppointmentId(): int {
        return $this->appointment;
    }
}