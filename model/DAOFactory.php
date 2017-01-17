<?php
/**
 * @author David Campos Rodríguez
 * @date 12/01/2017
 */

namespace model;

/**
 * Abstract class DAOFactory, factory for all the DAO's
 * @package model
 */
abstract class DAOFactory
{
    private static $singletonInstance = null;

    private function __construct() {
    }

    private function __clone() {
    }

    private function __wakeup() {
    }

    /**
     * Creates or gets the singleton instance of the factory.
     * Editing this method you can change the complete family in use.
     * @return MysqliDAOFactory
     */
    static public function getInstance(): DAOFactory {
        if (static::$singletonInstance === null) {
            return (static::$singletonInstance = new MysqliDAOFactory()); // Using MySqli
        } else
            return static::$singletonInstance;
    }

    abstract public function obtainUsersDAO();

    abstract public function obtainAppointmentsDAO();

    abstract public function obtainPropositionsDAO();

    abstract public function obtainSessionsDAO();
}

class MysqliDAOFactory extends DAOFactory
{
    public function obtainUsersDAO(): IUsersDAO {
        // TODO: Implement obtainUsersDAO() method.
    }

    public function obtainAppointmentsDAO(): IAppointmentsDAO {
        // TODO: Implement obtainAppointmentsDAO() method.
    }

    public function obtainPropositionsDAO(): IPropositionsDAO {
        // TODO: Implement obtainPropositionsDAO() method.
    }

    public function obtainSessionsDAO() {
        // TODO: Implement obtainSessionsDAO() method.
    }
}