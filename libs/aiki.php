<?php
/**
 * Aiki Framework (PHP)
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
 * @package     Library
 * @filesource
 */

if (!defined('IN_AIKI')) {
    die('No direct script access allowed');
}

/**
 * Stores config data and contains additional functions to process text,
 * output HTML, and generate clean URLs.
 * @category    Aiki
 * @package     System
 */
class aiki {

    private $pretty_url; // aiki store the pretty_url because some lib, need access / modify this url

    /**
     * return pretty url (path of url request)
     * Example www.foo.com/bar/something bar/something is the pretty url.
     * @return string
     */
    function pretty_url(){
        return $this->pretty_url;
    }


    public function __construct() {
        $this->pretty_url = isset($_GET["pretty"]) ?  $_GET["pretty"] : "" ;
    }


    /**
     * magic method that allowed on demand libs and extensions
     *
     * @return object loaded class or false
     */
    public function __get ($what) {
        return $this->load($what);
    }

    /**
     * Loads an aiki library.
     *
     * Attempts to load from class first *.php, then tries to load *.php
     * from extensions, then finally  tries classname/classname.php.
     *
     * @param   string $class name of class to be loaded
     * @global  string $AIKI_ROOT_DIR the full path to the Aiki root directory
     * @return  mixed
     */
    public function load($class) {
        global $AIKI_ROOT_DIR;

        if (isset($this->$class)){
            return $this->$class;
        }
        // Try to load the class file in /libs, assets/extensions and
        // assets/extension/$class/$class.php
        if (file_exists($AIKI_ROOT_DIR . '/libs/' . $class . '.php')) {
            require_once($AIKI_ROOT_DIR.'/libs/'.$class.'.php');
        } else {

            // filter extensions..
            $allowed = "," . $this->config->get("extensions_allowed", "ALL") . ",";
            // a tip..be sure "web" doesn't match "web2date".

            if ( $allowed != ",ALL," ) {
                if ( $allowed == ",NONE," or strpos($allowed, $class)===false ) {
                    return false;
                }
            }

            // search in dirs
            $SearchIn = $this->config->get("extensions_dir", "assets/extensions");
            $loaded = false;
            foreach (explode(",", $SearchIn) as $dir) {
                if (file_exists($AIKI_ROOT_DIR . "/$dir/$class.php")) {
                    require_once($AIKI_ROOT_DIR . "/$dir/$class.php");
                    $loaded= true;
                    break;
                }
                if (file_exists($AIKI_ROOT_DIR . "/$dir/$class/$class.php")) {
                    require_once($AIKI_ROOT_DIR . "/$dir/$class/$class.php");
                    $loaded= true;
                    break;
                }

            }
            if (!$loaded) {
                return false;
            }
        }

        $object = new $class();
        $this->$class = $object;
        return $object;
    }


    /**
     * Get configuration options for use.
     *
     * Get configuration items stored in the database
     * and append those items which are NOT set in
     * the configuration file. The configuration file
     * items take precedence and should NOT be overwriten.
     *
     * @param array $config The global configuration array
     * @global array $db The global database object
     * @return array     The global configuration array
     */

    public function get_config($config) {
        global $db;

        // get the config data stored in the database
        $settings = $db->get_results("SELECT config_data FROM aiki_config");

        // go through every config record. if the config item
        // is not already set then use the database record
        if ( !is_null($settings) ) {
            foreach ($settings as $setting_group) {

                // unserialize array key => value pairs stored
                // in this config group. Every row should be an array
                // of config items pertaining to a config group
                $temp = @unserialize($setting_group->config_data);

                if (is_array($temp)) {
                    // adding arrays doesn't overwrite the first parameter/array with
                    // the values of the second parameter/array when the keys match
                    $config = $config + $temp;
                }
            }
        }

        return $config;
    }

    /**
     * Get a String that is between two delimiters.
     *
     * @param  string   $string  the full string
     * @param  string   $start   first delimiter
     * @param  string   $end     second delimiter
     * @return string
     */
    public function get_string_between($string, $start, $end) {
        $ini = strpos($string,$start);
        if ( $ini ===false ) {
            return "";
        }
        $ini += strlen($start);
        $len = strpos($string,$end,$ini) - $ini;
        return substr($string,$ini,$len);
    }


    /**
     * Converts text to special characters.
     *
     * Works with HTML special characters and few other special characters
     * that PHP does not normally convert.
     *
     * @param   string   $text text to convert to special characters
     * @return  string
     */
    public function convert_to_specialchars($text) {

        $text = htmlspecialchars($text);

        $html_chars = array(")", "(", "[", "]", "{", "|", "}", "<", ">", "_");
        $html_entities = array("&#41;", "&#40;", "&#91;", "&#93;", "&#123;",
                               "&#124;", "&#125;", "&#60;", "&#62;", "&#95;");

        $text = str_replace($html_chars, $html_entities, $text);

        return $text;
    }

    /**
     * test if var match condition
     * Example ( *,foo) => true, (foo,foo)=>true, ( foo, !foo) false
     * @param string condition
     * @param string variable
     *
     * @return boolean
     */
    function match_pair_one($condition, $var) {
        if ($condition=='*' || $condition=='' || $condition==$var ||
             (substr($condition, 0, 1) == "!" &&  $condition <> "!$var")) {
            return true;
        }
        return false;
    }

    /**
     * test if var/var match condition
     * Example ( asterisk/es,foo/es) => true, (foo,foo/fr)=>true, ( foo/!ru, foo/ru) false
     * @param string condition
     * @param string $first
     * @param string $second
     *
     * @return boolean
     */
    function match_pair($condition, $first, $second="*", $third ="*") {
        //clean conditions,
        $condition= strtr(
                        $condition,
                        array("\n" =>" ",
                            ","  =>" ",
                            "\r" =>" "));
        $condition= preg_replace('/\s{2,}/', ' ', $condition); //clean double space
        $condition= trim($condition);

        $matches = explode(" ", $condition);

        foreach ($matches as $match) {
            $pair = explode("/", $match, 3) + array("*","*","*");
            if ($this->match_pair_one($pair[0],$first) &&
                 $this->match_pair_one($pair[1],$second) &&
                 $this->match_pair_one($pair[2],$third)) {
                return true;
            }
        }
        return false;
    }


    /**
     * Search first innest block in a text.
     * Example:
     * inner_markup ( "(2*(x+1)(z)", "(", ")" ,$position) =>return true
     *  and position of "x+1"
     *
     * @param string $string
     * @param string $startDelim
     * @param string $endDelim
     * @param byval array $position array(0=>start-position, 1=>end-position)
     *
     * @return boolean
     */
    function inner_markup($string, $startDelim, $endDelim, &$position) {
        $i= 10 ; //max level of recursion.

        $start = strpos($string, $startDelim);
        if ($start === false) {
            return false;
        }

        do {

            $end  = strpos($string, $endDelim, $start);
            if ($end === false) {
                return false;
            }
            $nested = strpos($string, $startDelim, $start+1);
            if ($nested === false || $nested > $end) {
                $position = array($start, $end) ;
                return true;
            }
            $start = $nested;
            $i--;
        } while ($i);
        return false;
    }

    /**
     * Search first outest block in a text.
     * Example:
     * outest block of ( (css((sql(good)sql))css) .. " is (css((sql(good)sql))css)";
     *
     * @param string $string
     * @param string $offset
     *
     * @return mixed  false if not found or array with 0:initial,1:lenght
     *                2 delim
     */

    function outer_markup($string, $offset = 0) {
        $i= 64; //max level of recursion.
        $delim = "";

        $temp = "";
        if (preg_match('/\(([a-z_]+)\(/U', $string, $temp)) {
            $delim = $temp[1];
            $startDelim = "($delim(";
            $endDelim = ")$delim)";
            $temp = "";
        } else {
            return false;
        }

        $initial = strpos($string, $startDelim, $offset);
        if ($initial === false) {
            return false;
        }

        $end = $initial;
        $nested = $initial;

        do {

            $nested = strpos($string, $startDelim, $nested+1);
            $end    = strpos($string, $endDelim, $end+1);
            if ($end === false) {
                return false;
            }
            if ($nested === false || $nested > $end) {
                return array($initial, $end - $initial, $delim);
            }

            $i--;
        } while ($i);
        return false;
    }


    /**
     * Eval a expression thats contains basic operators (+,-*,/),
     * parentsis, and variables.
     *
     * Example:
     *  $x*2
     *  ($x/2)-10
     *
     * @param string $expr String to be evaluated
     * @param array $var Variable defintion. Passed by reference for speed.
     *     will not be modified.
     *
     * @return number result
     */
    function eval_expression($expr, &$var) {
        $matches = 0;
        if ($expr == "") {
            return 0;
        } elseif (preg_match('/^[+-]?[0-9]*(\.[0-9]*)?$/', $expr, $matches)) {
            return (float) $expr;
        } elseif (preg_match('/^\$([a-z_]+[a-z_0-9]*)$/i', $expr, $matches)) {
            return (isset($var[$matches[1]]) ? $var[$matches[1]] : 0);
        } elseif (preg_match('/^(.*)\(([^\(\)]*)\)(.*)$/i', $expr, $matches)) {
            return meval($matches[1] . meval($matches[2], $var) . $matches[3], $var);
        } elseif (preg_match('~^(.*)([\+\-/\\\*%])(.*)$~', $expr, $matches)) {
             $op1= meval($matches[1], $var);
             $op2= meval($matches[3], $var);
             if (is_null($op1) || is_null($op2)) {
                 return NULL;
             }
             switch ($matches[2]) {
                 case "+": return $op1+$op2;
                 case "-": return $op1-$op2;
                 case "*": return $op1*$op2;
                 case "/": return (int)($op1/$op2);
                 case "\\": return $op1/$op2;
                 case "%": return $op1%$op2;
             }
        }
        return NULL;
    }


    /**
     * Replace Aiki vars with their assigned values.
     *
     * Use normal urls if mod_rewrite is not enabled.
     *
     * @param  string   $text before processing
     * @return string
     *
     * @global $aiki
     * @global $page
     *
     * @todo this function is seriously overloaded and needs to be rethought
     */
    public function processVars($text, $site="default") {
        static $bufferReplace;
        global $aiki, $page;

        $pretty = $aiki->config->get('pretty_urls', 1, "CURRENT", $site);
        $url = $aiki->config->get('url', false, "CURRENT", $site);

        if ( $bufferReplace == NULL ) {

            $current_month = date("n");
            $current_year = date("Y");
            $current_day = date("j");

            // calculate view, prefix, route
            $view = $aiki->site->view();
            $language = $aiki->site->language();
            $prefix = $aiki->site->prefix();
            $view_prefix = $aiki->site->view_prefix();

            $paths = array();
            if ($prefix) {
                $paths[] = $prefix;
            }
            if ($view_prefix) {
                $paths[] = $view_prefix;
            }
            if ( count($aiki->site->languages()) > 1 ) {
                $paths[] = $language;
            }
            $paths = implode("/",$paths);

            if (isset($_SERVER["HTTPS"])) {
                $url = str_replace("http://", "https://", $url);
            }
            $trimedUrl = preg_replace('#/$#',"",$url); // reg: remove ending /

            $bufferReplace = array(
                '[userid]'    => $aiki->membership->userid,
                '[full_name]' => $aiki->membership->full_name,
                '[username]'  => $aiki->membership->username,
                '[user_group_level]' => $aiki->membership->group_level,
                '[user_permissions]' => $aiki->membership->permissions,
                '[language]'  => $aiki->site->language(),
                '[page]'      => $page,
                '[site_name]' => $aiki->site->site_name(),
                '[site]'      => $aiki->site->get_site(),
                '[view]'      => $aiki->site->view(),
                '[direction]' => $aiki->languages->dir,
                'insertedby_username' => $aiki->membership->username,
                'insertedby_userid' => $aiki->membership->userid,
                'current_month' => $current_month,
                'current_year' => $current_year,
                'current_day' => $current_day,
                '[root]'          => $url,
                '[root-language]' => $trimedUrl .  "/" . $aiki->site->language(),
                '[site_prefix]'   => $prefix ,
                '[view_prefix]'   => $view_prefix ,
                '[route]'         => $trimedUrl.  "/". $paths,
                '[route-local]'     => $paths );

            // substitute all [POST[key]] and [GET[key]]
            if ( $aiki->config->get("show_gets", true) ){
                foreach ( $_GET as $key => $value ){
                    $bufferReplace["[GET[$key]]"] = $value;
                }
            }

            if ($aiki->config->get("show_posts", true)) {
                foreach ( $_POST as $key => $value ) {
                    $bufferReplace["[POST[$key]]"] = $value;
                }
            }

        }

        if (!$pretty) {
            /* TODO improve this*/
            $text = preg_replace(
                '/<a([^>]*)href\s*=\s*(["\'])\[root\](.*)\\2/U',
                '<a\\1href=\\2[root]?pretty=\\3\\2',
                $text);
        }

        $text = strtr($text, $bufferReplace);

        // remove all [GET[foo]] or [POST[bar]]
        $text = preg_replace('#\[(GET|POST)\[.*\]\]#U', "", $text );

        //@TODO by rg1024, this hack is necesary...
        $text = str_replace($url . '/', $url, $text);
        return $text;

    } // end of processVars method
} // end of aiki class

?>
