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
 * @todo		Translation of output messages.
 * @toto		error as html page.
 */

if(!defined('IN_AIKI')) {
	die('No direct script access allowed');
}


class Site {
 
	private $site; 
	private $site_name; 
	private $languages; // a array like [0]=>'en',[1]=>'fr'...
	private $need_translation;
	private $default_language;
	private $widget_language;
	private $site_prefix; 
	private $site_view;
	private $site_view_prefix;

	/**
	 * return site view 
	 * @return string 
	 */	  

	function view() {
		return $this->site_view;		
	}
	
	 /**
	 * return site view prefix
	 * @return string 
	 */	  

	function view_prefix() {
		return $this->site_view_prefix;		
	}
	
	
	/**
	 * return site prefix
	 * @return string 
	 */	  

	function prefix() {
		return $this->site_prefix;		
	}
	
	
	 /**
	 * return the default language of a site.
	 * @return string language
	 */	  
	function language($new=NULL) {		
		global $aiki;
		if (!is_null($new)) {
			if (in_array($new, $this->languages)) {	
				$this->default_language = $new;
				$this->need_translation = ($new != $this->widget_language);
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
	function languages() {
		return $this->languages;
	}  
	
	/**
	 * return site
	 * @return string Return site short name
	 */	 
	function get_site() {
		return $this->site;
	}   


	/**
	 * return site name
	 * @return string Return site long name
	 */
	function site_name() {
		return $this->site_name;
	}

	/**
	 * return true if site need to be translated
	 * @return boolean
	 */
	function need_translation() {
		return $this->need_translation;
	}

	/**
	 * magic method for use object site as a string
	 * @return string Return site long name
	 */

	function __toString() {
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

	function __construct() {
		global $db, $config, $aiki;
		
		// determine site name
		if (isset($_GET['site'])) {
			$config['site'] = addslashes($_GET['site']);
		} else {
			if (!isset($config['site'])) {
				$config['site'] = 'default';
			}	
		 
			// determine site by url (for multisite and apps)
			$this->site_prefix = "";							
			$path = $aiki->url->first_url();
			if ( $path != "homepage" ) {
				$site= $db->get_var("SELECT site_shortcut from aiki_sites where site_prefix='$path'");
				if ($site){
					$config['site'] = $site;					
					$aiki->url->shift_url();
					$this->site_prefix = $path;				  
				}	
			}						
		}
		
		// try read site information and test if is_active.
		$info = $db->get_row("SELECT * from aiki_sites where site_shortcut='{$config['site']}' limit 1");		
		if (is_null($info)) {		
			die($aiki->message->error("Fatal Error: Wrong site name provided. " ,NULL,false));
		} elseif ( $info->is_active != 1 ) {
			die($aiki->message->error($info->if_closed_output ? $info->if_closed_output : "Site {$config['site']} is closed.",
				NULL,
				false));
		}
			
		// determine view
		if (isset($_GET['view'])) {
			$this->site_view= addslashes($_GET['view']);			 
		} else {
			$prefix = $aiki->url->first_url();
			$view = $db->get_var("SELECT view_name, view_prefix FROM aiki_views " .
					" WHERE view_site='{$config['site']}' AND view_active='1' AND view_prefix='$prefix'");
			if ($view) {
				$aiki->url->shift_url();			
				$this->site_view = $view;
				$this->site_view_prefix = $prefix;						
			}
		}	 
								
		// define default language, list of allowed languages
		$this->default_language = ( $info->site_default_language ? 
			$info->site_default_language :
			"en" );
		$this->languages = ( $info->site_languages ? 
			explode(",", $info->site_languages) : 
			array($this->default_language) );
			
		$this->widget_language  = ( $info->widget_language ? 
			$info->widget_language : 
			$this->default_language );
		
		if (!in_array($this->default_language, $this->languages)) { 
			// correction: include default in allowed languages.
			$this->languages[]= $this->default_language;
		}				
		
		// determine language 
		if (isset($_GET['language'])) {			
			$this->language(addslashes($_GET['language']));
		} elseif ($this->language($aiki->url->first_url())) {
			$aiki->url->shift_url();			
		}
		$this->need_translation = ($this->default_language != $this->widget_language);

		//  the site manages dictionaries		
		if ( $this->default_language != "en" ) {
			$aiki->dictionary->add("core", new dictionaryTable($this->default_language));
			$aiki->dictionary->translateTo($this->default_language);
		}			
		
		if ( $this->widget_language != "en" ) {
			$aiki->dictionary->translateFrom($this->widget_language);
			$aiki->dictionary->add($config['site'], new dictionaryTable($this->default_language,$this->widget_language));
		}	

		// language
		$aiki->languages->set( $this->language());

		// site names
		$this->site = $config['site'];
		$this->site_name = $info->site_name;
	}
}

?>
