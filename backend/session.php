<?php
/**
 * This file is part of bw-post-digital.
 * Please check the file LICENSE.md for information about the license.
 *
 * @copyright Markus Riegert 2016
 * @author Markus Riegert <desmodul@drow-land.de>
 */

/**
 * Redis-based session handler for bw-post-digital.
 * 
 * Note that the SessionSaveHandler is autoloaded from the lib directory.
 * It implements all needed interface methods like open, close, ... . gc.
 * Should not be adapted, only thing to know is the \c sessionKeys index.
 */

/// register an own SessionSaveHandler to store session data in Rediska
$handler = new SessionSaveHandler($rediska, "bw-post-digital/sessionKeys/");

session_set_save_handler
(
    array($handler, 'open'),
    array($handler, 'close'),
    array($handler, 'read'),
    array($handler, 'write'),
    array($handler, 'destroy'),
    array($handler, 'gc')
);

// the following prevents unexpected effects when using objects as save handlers
register_shutdown_function('session_write_close');
session_start();
