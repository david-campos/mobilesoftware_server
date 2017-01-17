<?php

namespace model;


class MysqliUsersDAO extends MysqliDAO implements ISyncDAO, IUsersDAO
{

    /**
     * @param $userTO UserTO
     */
    function syncTO($userTO): void {
        $phone = $userTO->getPhone();
        $name = $userTO->getName();
        $picture = $userTO->getPictureId();
        $id = $userTO->getId();
        $stmt = $this->link->prepare('UPDATE `Users` SET `phone`=?,`name`=?,`picture_id`=? WHERE `_id`=?');
        $stmt->bind_param('ssii', $phone, $name, $picture, $id);
        $stmt->execute();
        $stmt->fetch();
        $stmt->close();
    }

    function obtainUserTO(string $phoneNumber): UserTO {
        $stmt = $this->link->prepare('SELECT `_id`,`phone`,`name`,`picture_id` FROM `Users` WHERE `phone`=? LIMIT 1');
        $stmt->bind_param('s', $phoneNumber);
        $stmt->execute();
        $stmt->bind_result($id, $phone, $name, $picture_id);
        $stmt->fetch();
        $stmt->close();
        $stmt = $this->link->prepare('SELECT `blocked` FROM `Blocked` WHERE `blocker`=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->bind_result($blocked);
        $blockedIds = array();
        while ($stmt->fetch()) {
            $blockedIds[] = $blocked;
        }
        $stmt->close();

        return new UserTO($id, $phone, $name, $picture_id, $blockedIds, $this);
    }

    function createUser(string $phoneNumber): void {
        $name = "New user";
        $pic = 0;
        $stmt = $this->link->prepare('INSERT VALUES(?,?,?) INTO `Users`(`phone`,`name`,`picture_id`)');
        $stmt->bind_param('ssi', $phoneNumber, $name, $pic);
        $stmt->execute();
        $stmt->close();
    }
}