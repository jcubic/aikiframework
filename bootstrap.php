<?php

/** Aiki Framework (PHP)
 *
 * This is the bootstrap file.
 *
 * LICENSE
 *
 * This source file is subject to the AGPL-3.0 license that is bundled
 * with this package in the file LICENSE.
 *
 * @author      Aikilab http://www.aikilab.com
 * @copyright   (c) 2008-2011 Aiki Lab Pte Ltd
 * @license     http://www.fsf.org/licensing/licenses/agpl-3.0.html
 * @link        http://www.aikiframework.org
 * @category    Aiki
 * @package     Aiki
 * @filesource
 */
/** Aiki Framework Version
 * The number left or west of the dots indicates a MAJOR production release.
 * In between the dots indicates a significant change or MINOR changes.
 * The number right or east of the dots indicates a bug FIX or small change.
 * When the MINOR number changes, the FIX number should reset to zero.
 * When the MAJOR number changes, the MINOR number should reset to zero.
 * When the MAJOR number is zero, this indicates an alpha or beta type
 * release. Each number can, but should probably not exceed 99
 * @TODO: Need to integrat the bzr revno into this after the X.X.X.REVNO
 *        since bassel nuked it because he didn't understand it.
 */
define('AIKI_VERSION','0.9.0.1');

/**
 * Used to test for script access
 */
define('IN_AIKI', true);

/** Determine the full path to the Aiki root directory.
 * @global string $AIKI_ROOT_DIR
 */
$AIKI_ROOT_DIR = realpath(dirname(__FILE__));

// append to the include path while preserving existing entries
set_include_path(
        get_include_path() .
        PATH_SEPARATOR .
        "$AIKI_ROOT_DIR");

/** @see AikiException.php */
require_once("$AIKI_ROOT_DIR/libs/AikiException.php");

/**
 * @todo these should be set in some class, and are scoped wrong
 */
if (isset($_GET['nogui'])) {
    $nogui = true;
}
if (isset($_GET['noheaders'])) {
    $noheaders = true;
}
if (isset($_GET['custom_output'])){
    $custom_output = true;
    $noheaders = true;
}

/**
 * Set the configuration option defaults.
 *
 */


/* use run-time installer config */
if (file_exists("$AIKI_ROOT_DIR/config.php")) {
    /** @see config.php */
    require_once("$AIKI_ROOT_DIR/config.php");
}
else {
    /** @see assets/apps/installer/installer.php */
    header("location:./assets/apps/installer/installer.php");
}

/* setting $config["log_level"] = "NONE" disables the log
 * or "None" and "none". Also if the log_level is not valid
* the log will default to disabled. */
/** @see Log.php */
require_once("$AIKI_ROOT_DIR/libs/Log.php");

/** Instantiate a new log for global use
 * @global Log $log */

$log = new Log($config["log_dir"],
        $config["log_file"],
        $config["log_level"],
        $AIKI_ROOT_DIR);

/* the following lines are usage examples:
 $log->message("test message which defaults to debug level");
$log->message("test ERROR", Log::ERROR);
$log->message("test WARN", Log::WARN);
$log->message("test INFO", Log::INFO);
$log->message("test DEBUG", Log::DEBUG);*/

/**
 * Where $db is defined as a switch in this 3rd party library.
 *
 * @see index.php
 */
require_once("$AIKI_ROOT_DIR/libs/database/index.php");

/**
 * @see aiki.php
 */
require_once("$AIKI_ROOT_DIR/libs/aiki.php");


/**
 * Global creation of the aiki instance.
 * @global aiki $aiki
 */
$aiki = new aiki();

/**
 * Get and store the configuration options.
 * @global array $config
 * @todo place by new config class.
 *
 */

$config = $aiki->get_config($config);

// if HTTP port is not 80, insert port in URL.
if (isset($config["url"]) && $_SERVER["SERVER_PORT"] != 80) {
    $config["url"] = preg_replace("#(http.?://[^/]*)(/)#",'$1:'. $_SERVER["SERVER_PORT"] . "/", $config["url"]);
}


/*
 * run time correction for site path
*/
if ($config['top_folder'] and $config['top_folder'] != $AIKI_ROOT_DIR){
    $config['top_folder'] = $AIKI_ROOT_DIR;
}

/*
 * Load some auxiliary classes
*/

$membership = $aiki->load("membership");

$aiki->load("message");
$aiki->load("dictionary");

/**
 * Load basic class: site, membership (permission), language, url(user requests)
 * @global membership $membership
 * @todo replace global membership by $aiki->membership
 */

$aiki->load('url');
$aiki->load('site');
$aiki->load('config');
$aiki->load("languages");
$aiki->load("input");

// this classes will be loaded by demand
/*
 $aiki->load("text");
$aiki->load("records");
$aiki->load("input");
$aiki->load("output");
$aiki->load("forms");
$aiki->load("AikiArray");
$aiki->load("security");
$aiki->load("parser");
$aiki->load("php");
$aiki->load("css_parser");
$aiki->load("sql_markup");
$aiki->load("view_parser");
$aiki->load("image");
$aiki->load("errors");*/

$aiki->load("Plugins");
