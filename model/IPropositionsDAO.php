<?php
/**
 * Created by PhpStorm.
 * User: ElJüsticieroMisteryo
 * Date: 14/01/2017
 * Time: 15:23
 */

namespace model;


interface IPropositionsDAO
{
    function obtainPropositionTO(int $appointmentId, int $timestamp, string $placeName): PropositionTO;

    /**
     * @param int $appointmentId
     * @return PropositionTO[]
     */
    function obtainPropositionsForAppointment(int $appointmentId): array;

    function createProposition(int $appointmentId, int $timestamp, string $placeName, array $coordinates,
                               string $reasonName, int $proposer): PropositionTO;

    function deleteProposition(PropositionTO $proposition): void;
}