<?php
/**
 * Created by PhpStorm.
 * User: ElJüsticieroMisteryo
 * Date: 17/01/2017
 * Time: 16:07
 */

namespace exceptions;

use Exception;

/**
 * Class NotUpdatedDescriptionException
 *
 * The reason description you are trying to get is not update. Please synchronize the TO
 * before trying to get this attribute.
 * @package exceptions
 */
class NotUpdatedDescriptionException extends Exception
{
}