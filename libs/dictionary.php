<?php

/**
 * Aiki Framework (PHP)
 *
 * LICENSE
 *
 * This source file is subject to the AGPL-3.0 license that is bundled
 * with this package in the file LICENSE.
 *
 * @author      Roger Martin
 * @copyright   (c) 2008-2011 Aiki Lab Pte Ltd
 * @license     http://www.fsf.org/licensing/licenses/agpl-3.0.html
 * @link        http://www.aikiframework.org
 * @category    Aiki
 * @package     Library
 */

if (!defined('IN_AIKI')) {
    die('No direct script access allowed');
}

include_once ("$AIKI_ROOT_DIR/libs/classes/dictionaryTableClass.php");

class Dictionary {
    private $tranlateTo;
    private $translateFrom;
    private $defaultDomain;
    private $noDomains;
    private $domains;

    function __construct(){
        global $aiki;
        $this->noDomains = true;
        $this->domains = array();
        $this->translateFrom = "en";
        $this->translateTo   = "en";
    }


    /**
     * Add a new domain to Dictionary store.
     *
     * A domain is a dictionary object that translate terms from certain language to other.
     *
     * @param string domain name
     * @param dictionary $dictionary object
     *
     */

    function add($domain,$dictionary){
        $to   = $dictionary->translateTo();
        $from = $dictionary->translateFrom();

        // set default domain and to translate if is first dictionary
        if (is_null($this->defaultDomain)) {
            $this->defaultDomain = $domain;
        }
        if (is_null($this->translateTo)) {
            $this->translateTo = $to;
        }

        $this->domains[$from][$to][$domain] = $dictionary;
        $this->noDomains = false;
    }

    /**
     * set/get default domain
     *
     * @param string (optional) new default domain
     * @return string actual domain or if given, return previus.
     *
     */

    function domain($new=NULL){
        $ret = $this->defaultDomain;
        if (!is_null($new)) {
            $this->defaultDomain = $new;
        }
        return $ret;
    }

    /**
     * set/get default target language (to be translated)
     *
     * @param string (optional) new language
     * @return string actual target language or if given, return previous.
     *
     */

    function translateTo($new=NULL){
        $ret = $this->translateTo;
        if (!is_null($new)) {
            $this->translateTo = $new;
        }
        return $ret;
    }

    /**
     * set/get original language
     *
     * @param string (optional) new default language
     * @return string actual language or if given, return previous.
     *
     */

    function translateFrom($new=NULL){
        $ret = $this->translateFrom;
        if (!is_null($new)) {
            $this->translateFrom = $new;
        }
        return $ret;
    }

    /**
     * translate a term
     *
     * @param $term to be translated
     * @param $translateFrom (optional), target language.
     * @param $translateTo (optional), term language.
     * @param $domain(optional), domain to be used.
     * @return string term translated
     *
     */
    function translate($term, $translateFrom=NULL, $translateTo=NULL, $domain=NULL) {
        $to = is_null($translateTo) ? $this->translateTo : $translateTo;
        $from = is_null($translateFrom)? $this->translateFrom : $translateFrom;

        if ( $to==$from || $this->noDomains )  {
            return $term;
        }

        $domain= is_null($domain) ? $this->defaultDomain : $domain;

        // PHP array !!
        if (isset($this->domains[$from][$to][$domain])) {
            return $this->domains[$from][$to][$domain]->translate($term);
        } else {
            return $term;
        }
    }
}

/**
 * Shortcut for use in aiki code, apps, extension and libs to translate a term
 *
 * Aiki is develop in english, and 'core' is the default dictionary.
 *
 * @param string $term to be translated
 * @param string $domain (optional, "core" by default)
 *
 */

function __($term, $domain=NULL) {
    global $aiki;
    $to = $aiki->site->language();
    if ( $to == "en" )  {
        return $term;
    }
    if (is_null($domain)) {
        $domain="core";
    }
    return $aiki->dictionary->translate($term, "en", $to, $domain);
}


/**
 * Shortcut for sprintf with translation
 *
 * @param string arg1 string formatted as sprinft
 * @param mixed rest
 *
 **************************************/

function __sprintf (){
    global $aiki;
    $to = $aiki->site->language();
    if ( $to == "en" )  {
        return $term;
    }

    $args = func_get_args();
    $args[0] = $aiki->dictionary->translate($args[0], "en", $to, "core");

    return call_user_func_array("sprintf", $args);
}



/**
 * translation of a term.
 *
 * __() functions assumes "en" as original language, t() doesn't.
 *
 *
 * @param string $term to be translated
 * @param string $domain (optional, "core" by default)
 *
 *
 **************************************/

function t($term, $domain=NULL){
    global $aiki;
    if (!$aiki->site->need_translation()) {
        return $term;
    }
    return $aiki->dictionary->translate(
        $term,
        $aiki->dictionary->translateFrom(),
        $aiki->dictionary->translateTo(),
        $domain);
}
