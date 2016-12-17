<?php
/**
 * This file is part of bw-post-digital.
 * Please check the file LICENSE.md for information about the license.
 *
 * @copyright Markus Riegert 2016
 * @author Markus Riegert <desmodul@drow-land.de>
 */

require_once 'lib/vendor/autoload.php';

$options = array('servers' => array(array('host' => '127.0.0.1', 'port' => 6379)));
$rediska = new Rediska($options);

/**
 * Custom auto loader for own files.
 *
 * Fill the \c $dirs array to add more relative include directory.
 *
 * @param string $_className The name of the class that gets sourced.
 */
function customAutoLoader($_className)
{
    $found = false;
    $dirs = array('lib') ;
    foreach ($dirs as $dir)
    {
        $file = __DIR__."/lib/".$dir."/".$_className. ".php";
        if (file_exists($file))
        {
            require $file;
            $found = true;
            break;
        }
    }
}
spl_autoload_register('customAutoLoader');

LoggerRegistry::createInstance(__DIR__.'/../logs/bw-post-digital-backend.log', Monolog\Logger::DEBUG);
