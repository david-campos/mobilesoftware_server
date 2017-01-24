<?php
/**
 * Created by PhpStorm.
 * User: David Campos R.
 * Date: 14/01/2017
 * Time: 15:22
 */

namespace model;


class MysqliPropositionsDAO extends MysqliDAO implements IPropositionsDAO
{
    public function obtainPropositionTO(int $appointmentId, int $timestamp, string $placeName): PropositionTO {
        $time = date('Y-m-d H:i:s', $timestamp);

        static::$link->begin_transaction();

        $stmt = static::$link->prepare('SELECT `appointment`,`timestamp`,`placeLat`,`placeLon`,`placeName`,`proposer`,`reason`
                                      FROM `Propositions` WHERE `appointment`=? AND `timestamp` = ? AND `placeName`=?
                                      LIMIT 1');
        $stmt->bind_param('iss', $appointmentId, $time, $placeName);
        $stmt->execute();
        $stmt->bind_result($appointmentId, $time, $placeLat, $placeLon, $placeName, $proposer, $reason);
        $stmt->fetch();
        $stmt->close();

        $stmt = static::$link->prepare('SELECT `name`, `description` FROM `Reasons` WHERE `name`=? LIMIT 1');
        $stmt->bind_param('s', $reason);
        $stmt->execute();
        $stmt->bind_result($reasonName, $reasonDescription);
        $stmt->fetch();
        $stmt->close();

        static::$link->commit();

        return new PropositionTO($appointmentId, $timestamp, $placeLat, $placeLon, $placeName, $reasonName, $reasonDescription, $proposer);
    }

    public function createProposition(int $appointmentId, int $timestamp, string $placeName, array $coordinates,
                                      string $reasonName, int $proposer): PropositionTO {
        $time = date('Y-m-d H:i:s', $timestamp);

        static::$link->begin_transaction();

        $stmt = static::$link->prepare('INSERT INTO `Propositions`(`appointment`,`timestamp`,`placeLat`,`placeLon`,
                                              `placeName`,`proposer`,`reason`) VALUES(?,?,?,?,?,?,?)');
        $stmt->bind_param('isddsis', $appointmentId, $time, $coordinates['lat'], $coordinates['lon'], $placeName, $proposer, $reasonName);
        $stmt->execute();
        $stmt->close();

        static::$link->commit();

        return $this->obtainPropositionTO($appointmentId, $timestamp, $placeName);
    }

    /**
     * @param int $appointmentId
     * @return PropositionTO[]
     */
    function obtainPropositionsForAppointment(int $appointmentId): array {
        static::$link->begin_transaction();

        $returnPropositions = array();

        $stmt = static::$link->prepare('SELECT `appointment`,`timestamp`,`placeLat`,`placeLon`,`placeName`,`proposer`,`reason`
                                      FROM `Propositions` WHERE `appointment`=?
                                      LIMIT 1');
        $stmt->bind_param('i', $appointmentId);
        $stmt->execute();
        $stmt->bind_result($appointmentId, $time, $placeLat, $placeLon, $placeName, $proposer, $reason);
        while ($stmt->fetch()) {
            $timestamp = strtotime($time);

            $stmt2 = static::$link->prepare('SELECT `name`, `description` FROM `Reasons` WHERE `name`=? LIMIT 1');
            $stmt2->bind_param('s', $reason);
            $stmt2->execute();
            $stmt2->bind_result($reasonName, $reasonDescription);
            $stmt2->fetch();
            $stmt2->close();

            $returnPropositions[] = new PropositionTO($appointmentId, $timestamp, $placeLat, $placeLon, $placeName,
                $reasonName, $reasonDescription, $proposer);
        }
        $stmt->close();

        static::$link->commit();

        return $returnPropositions;
    }

    function deleteProposition(PropositionTO $proposition): void {
        static::$link->begin_transaction();

        $app = $proposition->getAppointmentId();
        $time = date('Y-m-d H:i:s', $proposition->getTimestamp());
        $place = $proposition->getPlaceName();
        $stmt = static::$link->prepare('DELETE FROM `Propositions`
                                        WHERE `appointment`=? AND `timestamp`=? AND `placeName`=? LIMIT 1');
        $stmt->bind_param('iss', $app, $time, $place);
        $stmt->execute();
        $stmt->close();

        static::$link->commit();
    }
}