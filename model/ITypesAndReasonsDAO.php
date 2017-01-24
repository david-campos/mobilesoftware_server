<?php
/**
 * Created by PhpStorm.
 * User: David Campos R.
 * Date: 17/01/2017
 * Time: 18:42
 */

namespace model;


interface ITypesAndReasonsDAO
{
    public function getTypes(): array;

    public function getReasons(): array;
}