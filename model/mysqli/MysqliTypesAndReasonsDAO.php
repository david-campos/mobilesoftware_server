<?php
/**
 * Created by PhpStorm.
 * User: ElJüsticieroMisteryo
 * Date: 17/01/2017
 * Time: 18:46
 */

namespace model;


class MysqliTypesAndReasonsDAO extends MysqliDAO implements ITypesAndReasonsDAO
{
    public function getTypes(): array {
        static::$link->begin_transaction();

        $stmt = static::$link->prepare('SELECT `name`, `description`, `icon_id` FROM `AppointmentTypes`');
        $stmt->execute();
        $stmt->bind_result($name, $description, $icon_id);
        $types = array();
        while ($stmt->fetch()) {
            $types[] = array(
                'name' => $name,
                'description' => $description,
                'icon' => $icon_id
            );
        }

        static::$link->commit();

        return $types;
    }

    public function getReasons(): array {
        static::$link->begin_transaction();

        $stmt = static::$link->prepare('SELECT `name`, `description` FROM `Reasons`');
        $stmt->execute();
        $stmt->bind_result($name, $description);
        $reasons = array();
        while ($stmt->fetch()) {
            $reasons[] = array(
                'name' => $name,
                'description' => $description,
            );
        }

        static::$link->commit();
        return $reasons;
    }
}