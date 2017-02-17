<?php
/**
 * Created by PhpStorm.
 * User: David Campos R.
 * Date: 14/01/2017
 * Time: 15:22
 */

namespace model;


use DateTime;
use exceptions\UnableToGetTOException;

class MysqliPropositionsDAO extends MysqliDAO implements IPropositionsDAO
{
    public function obtainPropositionTO(int $appointmentId, int $timestamp, string $placeName): PropositionTO {
        $time = (new \DateTime('@' . $timestamp))->format('Y-m-d H:i:s');

        static::$link->begin_transaction();

        $stmt = static::$link->prepare('SELECT `appointment`,`timestamp`,`placeLat`,`placeLon`,`placeName`,`proposer`,`reason`
                                      FROM `Propositions` WHERE `appointment`=? AND `timestamp` = ? AND `placeName`=?
                                      LIMIT 1');
        $stmt->bind_param('iss', $appointmentId, $time, $placeName);
        $appid = $appointmentId;
        $place = $placeName;
        $timecpy = $time;
        $stmt->execute();
        $stmt->bind_result($appointmentId, $time, $placeLat, $placeLon, $placeName, $proposer, $reason);
        if (!$stmt->fetch()) {
            $stmt->close();
            throw new UnableToGetTOException("The requested PropositionTO doesn't exist (appointment=$appid,time=$timecpy,placeName=$place)");
        }
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

    /**
     * @param int $appointmentId
     * @param int $timestamp
     * @param string $placeName
     * @param array $coordinates
     * @param string|null $reasonName
     * @param int $proposer
     * @return PropositionTO
     */
    public function createProposition(int $appointmentId, int $timestamp, string $placeName, array $coordinates,
                                      $reasonName, int $proposer): PropositionTO {
        $time = (new \DateTime('@' . $timestamp))->format('Y-m-d H:i:s');

        static::$link->begin_transaction();

        $appid = $appointmentId;
        $place = $placeName;

        $stmt = static::$link->prepare('INSERT INTO `Propositions`(`appointment`,`timestamp`,`placeLat`,`placeLon`,
                                              `placeName`,`proposer`,`reason`) VALUES(?,?,?,?,?,?,?)');
        $stmt->bind_param('isddsis', $appointmentId, $time, $coordinates['lat'], $coordinates['lon'], $placeName, $proposer, $reasonName);
        $stmt->execute();
        $stmt->close();

        static::$link->commit();
        //die("Appid($appid) Place($place) ".gettype($appid)." ".gettype($place));
        return $this->obtainPropositionTO($appid, $timestamp, $place);
    }

    /**
     * @param int $appointmentId
     * @return PropositionTO[]
     */
    function obtainPropositionsForAppointment(int $appointmentId): array {
        static::$link->begin_transaction();

        $returnPropositions = array();

        $stmt = static::$link->prepare('SELECT p.`appointment`,p.`timestamp`,p.`placeLat`,p.`placeLon`,p.`placeName`,p.`proposer`,p.`reason`,r.`description`
                                      FROM `Propositions` p LEFT JOIN `Reasons` r ON p.`reason` = r.`name` WHERE p.`appointment`=?');
        $stmt->bind_param('i', $appointmentId);
        $stmt->execute();
        $stmt->bind_result($appointmentId, $time, $placeLat, $placeLon, $placeName, $proposer, $reason, $reasonDescription);
        while ($stmt->fetch()) {
            $timestamp = DateTime::createFromFormat('Y-m-d H:i:s', $time, new \DateTimeZone('UTC'))->getTimestamp();

            $returnPropositions[] = new PropositionTO($appointmentId, $timestamp, $placeLat, $placeLon, $placeName,
                $reason, $reasonDescription, $proposer);
        }
        $stmt->close();

        static::$link->commit();

        return $returnPropositions;
    }

    function deleteProposition(PropositionTO $proposition) {
        static::$link->begin_transaction();

        $app = $proposition->getAppointmentId();
        $time = (new \DateTime('@' . $proposition->getTimestamp()))->format('Y-m-d H:i:s');
        $place = $proposition->getPlaceName();
        $stmt = static::$link->prepare('DELETE FROM `Propositions`
                                        WHERE `appointment`=? AND `timestamp`=? AND `placeName`=? LIMIT 1');
        $stmt->bind_param('iss', $app, $time, $place);
        $stmt->execute();
        $stmt->close();

        static::$link->commit();
    }
}