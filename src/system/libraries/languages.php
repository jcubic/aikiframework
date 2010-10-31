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
 * @copyright   (c) 2008-2010 Aikilab
 * @license     http://www.fsf.org/licensing/licenses/agpl-3.0.html
 * @link        http://www.aikiframework.org
 * @category    Aiki
 * @package     Library
 * @filesource
 */

if(!defined('IN_AIKI')){die('No direct script access allowed');}

/**
 * BriefDescription
 *
 * @category    Aiki
 * @package     Library
 */
class languages
{


	public function L10n($string){
		global $db, $config;

		$count = preg_match_all( '/\_\_(.*)\_\_/U', $string, $matches );
		if ($count >0){

			$query = "where";

			$default_language = "lang_".$config['default_language'];

			foreach ($matches[1] as $parsed)
			{
				$parsed = trim($parsed);

				$query .= ' short_term ="'.$parsed.'" or ';
			}

			$query = preg_replace('/ or $/i', '', $query);

			$terms = $db->get_results("SELECT short_term, $default_language FROM aiki_dictionary $query");

			if ($terms){
				foreach ($terms as $term){

					$string = str_replace("__".$term->short_term."__", $term->$default_language, $string);
				}
			}

		}
		return $string;
	}


}
?>