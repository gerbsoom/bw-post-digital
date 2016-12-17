<?php
/**
 * This file is part of bw-post-digital.
 * Please check the file LICENSE.md for information about the license.
 *
 * @copyright Markus Riegert 2016
 * @author Markus Riegert <desmodul@drow-land.de>
 */

 /**
 * Entry point for bw-post-digital.
 */

// source loading stuff
require_once("boot.php");

// and the session handler
require_once("session.php");

/**
 * Exits with an error message.
 *
 * @param string $_errorMessage The error message gets displayed.
 */
function dieOutputError($_errorMessage)
{
    $log = LoggerRegistry::getLogger("entrypoint_DIED_");
    $log->err($_errorMessage);
    die($_errorMessage);
}

/**
 * Returns the BackendResult which was generated in the corresponding controller or handler.
 *
 * @param string $_response The generated BackendResult as JSON result.
 */
function sendResponse($_response)
{
    $log = LoggerRegistry::getLogger("entrypoint_SUCCESS_");
    $log->debug($_response);
    header('Content-type: application/json');
    echo $_response;
}

/**
 * Returns the requested key from GET or POST parameters.
 *
 * toDo: Remove when the stuff works, we want only POST.
 *
 * @param string $_key The key of the requested value.
 * @return string The retrieved value.
 */
function submittedValueFor($_key)
{
    if (isset($_POST[$_key]))
    {
        return $_POST[$_key];
    }
    else  if (isset($_GET[$_key]))
    {
        return $_GET[$_key];
    }
    else return "";
}

// Logging stuff setup in boot.php, so grab one
$log = LoggerRegistry::getLogger("entrypoint");


$components = new Components();
$actions = $components->getActions();
$controller = $components->getController();

$loginName = submittedValueFor("loginName");
$controllerName = submittedValueFor("controller");
$_SESSION['user']['username'] = $loginName;
if (in_array($controllerName, array_keys($actions)))
{
    $action = submittedValueFor("action");
    $log->debug("_____________________________________________________________________________________________________");
    $log->debug("Serving request [$controllerName|$action] for user: $loginName");
    if (in_array($action, $actions[$controllerName]["actions"]))
    {
        $parsedParameters = array("controller"=>$controllerName, "action"=>$action);
        foreach ($actions[$controllerName]["params"] as $parameter)
        {
            $value = submittedValueFor($parameter);
            if ($value) $log->debug(" --> parameter[$parameter] = '$value'");
            $parsedParameters[$parameter] = $value;
        }
        /** @var ControllerBase $requestedController */
        $requestedController = new $controller[$controllerName]($parsedParameters);
        if ($requestedController->validateParameters())
        {
            if ($requestedController->checkPermissions())
            {
                sendResponse($requestedController->executeAction());
            }
            else dieOutputError("Insufficient permissions at [$controllerName::$action] for user #$loginName");
        }
        else dieOutputError("Error validating required parameters in [$controllerName::$action] for user #$loginName");
    }
    else dieOutputError("Invalid action [$controllerName::$action] for user #$loginName");
}
else dieOutputError("Invalid controller ($controllerName) for user #$loginName");
