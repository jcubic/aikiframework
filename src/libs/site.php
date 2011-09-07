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
 * @filesource
 *
 * @todo        Translation of output messages.
 * @toto        error as html page.
 */

if(!defined('IN_AIKI')){die('No direct script access allowed');}


class Site {
 
    private $site ; 
    private $site_name; 
    private $languages; // a array like [0]=>'en',[1]=>'fr'...
    private $need_translation;
    private $default_language;
    private $pretty_url;
    private $site_prefix; // 


    /**
     * return site prefix (beginin with "/" or blank space)
     * @return string 
     */      

    function prefix(){
        return $this->site_prefix;        
    }

    /**
     * return the pretty url ( with site path removed )
     * @return string 
     */      

    function pretty_url(){
        return $this->pretty_url;        
    }
    
     /**
     * return the default language of a site.
     * @return array languages
     */      
    function language($new=NULL){        
        if ( !is_null($new) ){
            if ( in_array($new, $this->languages) ){
                $this->default_language= $new;
                $this->need_translation = ( $new != $this->widget_language);
                return true;                
            } else {
                return false;
            }     
        }
        return $this->default_language;
    }  
    
    
    /**
     * return list (array) of allowed languages in a site.
     * @return array languages
     */     
    function languages(){
        return $this->languages;
    }  
    
    /**
     * return site
     * @return string Return site short name
     */     
    function get_site(){
        return $this->site;
    }   


    /**
     * return site name
     * @return string Return site long name
     */
    function site_name(){
        return $this->site_name;
    }

    /**
     * return true if site content (generated by widget) need to be 
     * translated
     * @return boolean
     */
    function need_translation(){
        return $this->need_translation;
    }


    /**
     * magic method for use object site as a string
     * @return string Return site long name
     */

    function __toString(){
        return $this->site;
    }

    /** 
     * Constructor: set site name or die if is inexistent or inactive.
     * The site is established by (in this order), GET[site], $config[site]
     * and finaly Default.
     * 
     * @global $db
     * @global $config 
     */

    function __construct(){
        global $db, $config, $aiki;
        
        // determine site name
        if (isset($_GET['site'])) {
            $config['site'] = addslashes($_GET['site']);
        } elseif ( !isset( $config['site'] )) {
            $config['site'] = 'default';
        }  
        
        // determine site by url (for multisite and apps)
        $this->site_prefix = "";                    
        $this->pretty_url= $aiki->pretty_url();        
        if ( $this->pretty_url ){
            $paths = explode("/", str_replace("|", "/", $this->pretty_url));
            if ( $paths[0] ) {                
                $site= $db->get_var("SELECT site_shortcut from aiki_sites where site_prefix='{$paths[0]}'" );
                if ( $site ){
                    $config['site'] = $site;                    
                    $this->pretty_url = count($paths)==1 ? "" : substr( $this->pretty_url, strpos($this->pretty_url,"/")+1);
                    $this->site_prefix = "{$paths[0]}";                    
                }    
            }                
        } 
        
        // try read site information and test if is_active.
        $info = $db->get_row("SELECT * from aiki_sites where site_shortcut='{$config['site']}' limit 1");
        $error = false;
        if ( is_null($info) ) {        
            $error =  "Fatal Error: Wrong site name provided. " .
                      (defined('ENABLE_RUNTIME_INSTALLER') && ENABLE_RUNTIME_INSTALLER == FALSE ?
                      "ENABLE_RUNTIME_INSTALLER is set to FALSE." : "");      
        } elseif ( $info->is_active != 1) {
            $error = $info->if_closed_output ? 
                $info->if_closed_output : 
                "Site {$config['site']} is closed.";
            
        }
        if ( $error ){
            die( $aiki->message->error( $error, NULL, false) ); 
        }    
        
        // language settings.
        $this->default_language = ( $info->site_default_language ? 
            $info->site_default_language :
            "en");
        $this->languages        = ( $info->site_languages ? 
            explode(",",$info->site_languages) : 
            array("en") );
        $this->widget_language  = ( $info->widget_language ? 
            $info->widget_language : 
            $this->default_language );
        
        if ( !in_array( $this->default_language, $this->languages) ) { 
            // correction: include default in allowed languages.
            $this->languages[]= $this->default_language;
        }                
        $this->need_translation = ( $this->default_language != $this->widget_language);
        
        // site names
        $this->site      = $config['site'];
        $this->site_name = $info->site_name;
    }
}
