<?php
/**
 * Created by PhpStorm.
 * User: ElJüsticieroMisteryo
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

        $stmt = static::$link->prepare('INSERT VALUES(?,?,?,?,?,?,?)
                                  INTO `Propositions`(`appointment`,`timestamp`,`placeLat`,`placeLon`,`placeName`,`proposer`,`reason`)');
        $stmt->bind_param('isddsis', $appointmentId, $time, $coordinates['lat'], $coordinates['lon'], $placeName, $proposer, $reasonName);
        $stmt->execute();
        $stmt->close();

        static::$link->commit();

        return $this->obtainPropositionTO($appointmentId, $timestamp, $placeName);
    }
}