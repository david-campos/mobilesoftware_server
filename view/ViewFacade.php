<?php
/**
 * Created by PhpStorm.
 * User: David Campos R.
 * Date: 22/01/2017
 * Time: 17:49
 */

namespace view;

require_once dirname(__FILE__) . '/Outputter.php';
require_once dirname(__FILE__) . '/OutputterJSON.php';

class ViewFacade
{
    public function getOutputter(): Outputter {
        return new OutputterJSON();
    }
}