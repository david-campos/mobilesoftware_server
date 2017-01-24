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
     * @return DAOFactory
     */
    static public function getInstance(): DAOFactory {
        if (static::$singletonInstance === null) {
            return (static::$singletonInstance = new MysqliDAOFactory()); // Using MySqli
        } else
            return static::$singletonInstance;
    }

    abstract public function obtainUsersDAO(): IUsersDAO;

    abstract public function obtainAppointmentsDAO(): IAppointmentsDAO;

    abstract public function obtainPropositionsDAO(): IPropositionsDAO;

    abstract public function obtainSessionsDAO(): SessionsDAO;

    abstract public function obtainTypesAndReasonsDAO(): ITypesAndReasonsDAO;
}

class MysqliDAOFactory extends DAOFactory
{
    public function obtainUsersDAO(): IUsersDAO {
        return new MysqliUsersDAO();
    }

    public function obtainAppointmentsDAO(): IAppointmentsDAO {
        return new MysqliAppointmentsDAO();
    }

    public function obtainPropositionsDAO(): IPropositionsDAO {
        return new MysqliPropositionsDAO();
    }

    public function obtainSessionsDAO(): SessionsDAO {
        return new SessionsDAO(new MysqliSessionsDAO());
    }

    public function obtainTypesAndReasonsDAO(): ITypesAndReasonsDAO {
        return new MysqliTypesAndReasonsDAO();
    }
}