<?php
/**
 * Created by PhpStorm.
 * User: David Campos R.
 * Date: 14/01/2017
 * Time: 15:22
 */

namespace model;


use DateTime;
use Exception;

class MysqliAppointmentsDAO extends MysqliDAO implements IAppointmentsDAO, ISyncDAO, IInvitationsUpdater
{
    private function getInvitationsFor(int $id): array {
        $invitations = array();
        $stmt = static::$link->prepare('SELECT i.user, i.state, i.reason, r.description
                                       FROM InvitedTo i LEFT JOIN Reasons r ON r.name=i.reason 
                                       WHERE appointment=?
                                       LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->bind_result($user, $state, $reason, $description);
        while ($stmt->fetch()) {
            if ($reason !== null && $description !== null) {
                $invitation = new Invitation($user, $state, $reason, $description);
            } else {
                $invitation = new Invitation($user, $state);
            }
            $invitations[] = $invitation;
        }
        $stmt->close();

        return $invitations;
    }

    function obtainAppointmentTO(int $id): AppointmentTO {
        static::$link->begin_transaction();

        $stmt = static::$link->prepare('SELECT name, description, closed, type, creator, currentProposal, currentPlaceName
                                      FROM Appointments
                                      WHERE _id=?
                                      LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->bind_result($name, $description, $closed, $type, $creator, $currentProposal, $currentPlaceName);
        $stmt->fetch();
        $stmt->close();

        $currentProposal = DateTime::createFromFormat('Y-m-d H:i:s', $currentProposal, new \DateTimeZone('UTC'))->getTimestamp();
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
        $stmt = static::$link->prepare('INSERT INTO Appointments(name,description,closed,type,creator)
                                      VALUES(?,?,?,?,?)');
        $stmt->bind_param('ssiss', $name, $description, $closed, $typeName, $creatorId);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();

        // Check invited users that blocked the creator and remove them from the list
        $stmtBlocked = static::$link->prepare('SELECT b.blocked FROM Blocked b JOIN Users u ON(b.blocker=u._id)
                                            WHERE u.phone=? AND b.blocked=? LIMIT 1');
        $stmtBlocked->bind_param('ii', $blocker, $blocked);
        for ($idx = 0; $idx < count($invitedUsers); $idx++) {
            $blocker = $invitedUsers[$idx];
            $blocked = $creatorId;
            $stmtBlocked->execute();
            $stmtBlocked->store_result();
            if ($stmtBlocked->num_rows > 0) {
                array_splice($invitedUsers, $idx, 1);
                $idx--; // To avoid jumping the next one
            }
        }
        $stmtBlocked->close();

        // Inserting invitations
        $stmtInvitation = static::$link->prepare('INSERT INTO InvitedTo(user,appointment)
                                                  VALUES ((SELECT _id FROM Users WHERE phone=? LIMIT 1), ?)');
        $reallyInvited = 0;
        foreach ($invitedUsers as $invited) {
            $stmtInvitation->bind_param('ii', $invited, $id);
            try {
                $stmtInvitation->execute();
                $reallyInvited += $stmtInvitation->affected_rows;
            } catch (Exception $e) {
                // Ignore 1048, probably phone incorrect
                if ($e->getCode() != 1048) throw $e;
            }
        }
        $stmtInvitation->close();

        if ($reallyInvited < 1) {
            static::$link->rollback();
            throw new \Exception('No valid invitations for the new appointment', 200);
        }

        // Inserting initial proposition
        $proposition = DAOFactory::getInstance()->obtainPropositionsDAO()->createProposition(
            $id, $initialPropositionModel->getTimestamp(), $initialPropositionModel->getPlaceName(),
            $initialPropositionModel->getCoordinates(), $initialPropositionModel->getReasonName(), $creatorId);

        // Linking intitial proposition
        $proposalTimestamp = (new \DateTime('@' . $proposition->getTimestamp()))->format('Y-m-d H:i:s');
        $proposalPlace = $proposition->getPlaceName();
        $stmt = static::$link->prepare('UPDATE Appointments SET currentProposal=?, currentPlaceName=?
                                      WHERE _id=? LIMIT 1');
        $stmt->bind_param('ssi', $proposalTimestamp, $proposalPlace, $id);
        $stmt->execute();
        $stmt->close();

        static::$link->commit();

        return $id;
    }

    /**
     * Take in account IT DOESN'T UPDATE INVITATIONS, so by now invitations are constant, impossible to modify
     * after been created.
     * @param $TO AppointmentTO
     */
    function syncTO($TO) {
        static::$link->begin_transaction();

        $name = $TO->getName();
        $description = $TO->getDescription();
        $closed = ($TO->isClosed() ? 1 : 0);
        $type = $TO->getTypeName();
        $creator = $TO->getCreatorId();
        $currentProposal = (new \DateTime('@' . $TO->getCurrentProposition()->getTimestamp()))->format('Y-m-d H:i:s');
        $currentPlaceName = $TO->getCurrentProposition()->getPlaceName();
        $id = $TO->getId();

        $stmt = static::$link->prepare('UPDATE Appointments
                                      SET name=?, description=?, closed=?, type=?, creator=?,
                                        currentProposal=?, currentPlaceName=?
                                      WHERE _id=? LIMIT 1');
        $stmt->bind_param('ssisissi', $name, $description, $closed, $type, $creator, $currentProposal, $currentPlaceName, $id);
        $stmt->execute();
        $stmt->close();

        $inv_appo = $inv_reason = $inv_state = $inv_user = null;
        $stmt = static::$link->prepare('UPDATE InvitedTo SET reason=?, state=?
                                        WHERE user=? AND appointment=? LIMIT 1');
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

        $stmt = static::$link->prepare('SELECT a._id, a.name, a.description, a.closed, a.type, a.creator, a.currentProposal, a.currentPlaceName
                                      FROM Appointments a LEFT JOIN InvitedTo i ON a._id = i.appointment
                                      WHERE a.creator=? OR i.user = ? 
                                      GROUP BY a._id');
        $stmt->bind_param('ii', $userId, $userId);
        $stmt->execute();
        $array = array();
        $stmt->bind_result($id, $name, $description, $closed, $type, $creator, $currentProposal, $currentPlaceName);
        $stmt->store_result(); // So we can do other statement consults
        while ($stmt->fetch()) {
            $currentProposal = DateTime::createFromFormat(
                'Y-m-d H:i:s', $currentProposal, new \DateTimeZone('UTC'))->getTimestamp();

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

    public function loadInvitationsFromBD(AppointmentTO $appointment) {
        static::$link->begin_transaction();

        $appointment->deleteInvitations();
        $invitations = $this->getInvitationsFor($appointment->getId());
        $appointment->addInvitations($invitations);

        static::$link->commit();
    }
}