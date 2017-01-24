<?php
/**
 * Created by PhpStorm.
 * User: ElJüsticieroMisteryo
 * Date: 14/01/2017
 * Time: 15:22
 */

namespace model;


class MysqliAppointmentsDAO extends MysqliDAO implements IAppointmentsDAO, ISyncDAO, IInvitationsUpdater
{
    private function getInvitationsFor(int $id): array {
        $invitations = array();
        $stmt = static::$link->prepare('SELECT `user`, `state`, `reason`
                                       FROM `InvitedTo`
                                       WHERE `appointment`=?
                                       LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->bind_result($user, $state, $reason);

        $stmtInvitation = static::$link->prepare('SELECT `description` FROM `Reasons` WHERE `name`=? LIMIT 1');
        $stmtInvitation->bind_param('s', $reason);
        $stmtInvitation->bind_result($description);
        while ($stmt->fetch()) {
            if ($reason !== null) {
                $stmtInvitation->execute();
                $stmtInvitation->fetch();
                if ($description !== null)
                    $invitation = new Invitation($user, $state, $reason, $description);
                else
                    $invitation = new Invitation($user, $state);
            } else {
                $invitation = new Invitation($user, $state);
            }
            $invitations[] = $invitation;
        }
        $stmtInvitation->close();
        $stmt->close();

        return $invitations;
    }

    function obtainAppointmentTO(int $id): AppointmentTO {
        static::$link->begin_transaction();

        $stmt = static::$link->prepare('SELECT `name`, `description`, `closed`, `type`, `creator`, `currentProposal`, `currentPlaceName`
                                      FROM `Appointments`
                                      WHERE `id`=?
                                      LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->bind_result($name, $description, $closed, $type, $creator, $currentProposal, $currentPlaceName);
        $stmt->fetch();
        $stmt->close();

        $currentProposal = strtotime($currentProposal);
        $proposition = DAOFactory::getInstance()->obtainPropositionsDAO()
            ->obtainPropositionTO($id, $currentProposal, $currentPlaceName);

        $invitations = $this->getInvitationsFor($id);

        $appointment = new AppointmentTO($name, $description, $closed, $id, $type, $creator, $proposition, $invitations,
            $this, $this);
        static::$link->commit();

        return $appointment;
    }

    function createAppointment(string $name, string $description, bool $closed, string $typeName, int $creatorId,
                               array $invitedUsers, PropositionTO $initialPropositionModel): int {
        static::$link->begin_transaction();

        // Inserting appointment
        $closed = ($closed ? 1 : 0);
        $stmt = static::$link->prepare('INSERT INTO `Appointments`(`name`,`description`,`closed`,`type`,`creator`)
                                      VALUES(?,?,?,?,?)');
        $stmt->bind_param('ssiss', $name, $description, $closed, $typeName, $creatorId);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();

        // Inserting invitations
        $stmtInvitation = static::$link->prepare('INSERT INTO `InvitedTo`(`user`,`appointment`)
                                                VALUES (?,?)');
        foreach ($invitedUsers as $invited) {
            $stmtInvitation->bind_param('ii', $invited, $id);
            $stmtInvitation->execute();
        }
        $stmtInvitation->close();

        // Inserting initial proposition
        $proposition = DAOFactory::getInstance()->obtainPropositionsDAO()->createProposition(
            $id, $initialPropositionModel->getTimestamp(), $initialPropositionModel->getPlaceName(),
            $initialPropositionModel->getCoordinates(), $initialPropositionModel->getReasonName(), $creatorId);

        // Linking intitial proposition
        $proposalTimestamp = date('Y-m-d H:i:s', $proposition->getTimestamp());
        $proposalPlace = $proposition->getPlaceName();
        $stmt = static::$link->prepare('UPDATE `Appointments` SET `currentProposal`=?, `currentPlaceName`=?
                                      WHERE `_id`=? LIMIT 1');
        $stmt->bind_param('ss', $proposalTimestamp, $proposalPlace);
        $stmt->execute();
        $stmt->close();

        static::$link->commit();
    }

    /**
     * Take in account IT DOESN'T UPDATE INVITATIONS, so by now invitations are constant, impossible to modify
     * after been created.
     * @param $TO AppointmentTO
     */
    function syncTO($TO): void {
        static::$link->begin_transaction();

        $name = $TO->getName();
        $description = $TO->getDescription();
        $closed = ($TO->isClosed() ? 1 : 0);
        $type = $TO->getTypeName();
        $creator = $TO->getCreatorId();
        $currentProposal = date('Y-m-d H:i:s', $TO->getCurrentProposition()->getTimestamp());
        $currentPlaceName = $TO->getCurrentProposition()->getPlaceName();

        $stmt = static::$link->prepare('UPDATE `Appointments`
                                      SET `name`=?, `description`=?, `closed`=?, `type`=?, `creator`=?,
                                        `currentProposal`=?, `currentPlaceName`=?
                                      WHERE `_id`=? LIMIT 1');
        $stmt->bind_param('ssisiss', $name, $description, $closed, $type, $creator, $currentProposal, $currentPlaceName);
        $stmt->execute();
        $stmt->close();

        $inv_appo = $inv_reason = $inv_state = $inv_user = null;
        $stmt = static::$link->prepare('UPDATE `InvitedTo` SET `reason`=?, `state`=?
                                        WHERE `user`=? AND `appointment`=? LIMIT 1');
        $stmt->bind_param('ssii', $inv_reason, $inv_state, $inv_user, $inv_appo);
        $inv_appo = $TO->getId();
        foreach ($TO->getInvitations() as $invitation) {
            $inv_reason = $invitation->getReasonName();
            $inv_state = $invitation->getState();
            $inv_user = $invitation->getUser();

            $stmt->execute();
        }
        $stmt->close();

        static::$link->commit();
    }

    function obtainAppointmentsOfUser(int $userId): array {
        static::$link->begin_transaction();

        $stmt = static::$link->prepare('SELECT `name`, `description`, `closed`, `type`, `creator`, `currentProposal`, `currentPlaceName`
                                      FROM `Appointments` a JOIN `InvitedTo` i ON a.`_id` = i.`appointment`
                                      WHERE a.`creator`=? OR i.`user` = ? 
                                      GROUP BY a.`_id`');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $array = array();
        $stmt->bind_result($name, $description, $closed, $type, $creator, $currentProposal, $currentPlaceName);
        while ($stmt->fetch()) {
            $currentProposal = strtotime($currentProposal);
            $proposition = DAOFactory::getInstance()->obtainPropositionsDAO()
                ->obtainPropositionTO($id, $currentProposal, $currentPlaceName);

            $invitations = $this->getInvitationsFor($id);

            $appointment = new AppointmentTO($name, $description, $closed, $id, $type, $creator, $proposition, $invitations,
                $this, $this);

            $array[] = $appointment;
        }
        $stmt->close();

        static::$link->commit();

        return $array;
    }

    public function loadInvitationsFromBD(AppointmentTO $appointment): void {
        static::$link->begin_transaction();

        $appointment->deleteInvitations();
        $invitations = $this->getInvitationsFor($appointment->getId());
        $appointment->addInvitations($invitations);

        static::$link->commit();
    }
}