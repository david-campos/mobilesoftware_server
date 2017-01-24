<?php
/**
 * Created by PhpStorm.
 * User: David Campos R.
 * Date: 14/01/2017
 * Time: 13:36
 */

namespace model;


abstract class AbstractTO
{
    protected $synchronized;
    private $dao;

    public function __construct(ISyncDAO $dao) {
        $this->dao = $dao;
        $this->synchronized = true;
    }

    public function isSynchronized(): bool {
        return $this->synchronized;
    }

    public function synchronize() {
        $this->dao->syncTO($this);
        $this->synchronized = true;
    }
}