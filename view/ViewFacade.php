<?php
/**
 * Created by PhpStorm.
 * User: David Campos R.
 * Date: 22/01/2017
 * Time: 17:49
 */

namespace view;


class ViewFacade
{
    public function getOutputter(): Outputter {
        return new OutputterJSON();
    }
}