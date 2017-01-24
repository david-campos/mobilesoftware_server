<?php
/**
 * Created by PhpStorm.
 * User: David Campos R.
 * Date: 17/01/2017
 * Time: 18:45
 */

namespace model;


class MysqliSessionsDAO extends MysqliDAO implements ISessionsDAO
{
    public function insertSession(string $phone, string $key): int {
        static::$link->begin_transaction();

        $now = date('Y-m-d H:i:s');
        $stmt = static::$link->prepare('INSERT INTO `Sessions`(`user`, `session_key`, `initial_timestamp`)
                                        VALUES(
                                          (
                                            SELECT `_id` FROM `Users` WHERE `phone`=? LIMIT 1
                                          ),?,?)');
        $stmt->bind_param('sss', $phone, $key, $now);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();

        static::$link->commit();

        return $id;
    }

    public function getSessionKey(int $id, string $phone): string {
        static::$link->begin_transaction();

        $stmt = static::$link->prepare('SELECT `session_key` FROM `Sessions`
                                        WHERE `user` = (
                                            SELECT `_id` FROM `Users` WHERE `phone`=? LIMIT 1
                                          ) AND _id = ?
                                        LIMIT 1');
        $stmt->bind_param('si', $phone, $id);
        $stmt->execute();
        $stmt->bind_result($key);
        $stmt->fetch();
        $stmt->close();

        static::$link->commit();

        return $key;
    }

    public function closeSession(int $id): void {
        static::$link->begin_transaction();

        $now = date('Y-m-d H:i:s');
        $stmt = static::$link->prepare('UPDATE `Sessions` SET `final_timestamp`=?
                                        WHERE _id = ?
                                        LIMIT 1');
        $stmt->bind_param('si', $now, $id);
        $stmt->execute();
        $stmt->close();

        static::$link->commit();
    }
}