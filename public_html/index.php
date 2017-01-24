<?php
/**
 * User: David Campos R.
 * Date: 24/01/2017
 * Time: 14:44
 */
require_once dirname(__FILE__) . '/../controller/RequestProcessor.php';

$request_processor = new \controller\RequestProcessor();
try {
    $request_processor->processRequest($_GET);
} catch (Exception $e) {
    $request_processor->getOutputter()->printError($e->getMessage() . PHP_EOL . $e->getTraceAsString(), $e->getCode());
} catch (Error $e) {
    $request_processor->getOutputter()->printError($e->getMessage() . PHP_EOL . $e->getTraceAsString(), $e->getCode());
}