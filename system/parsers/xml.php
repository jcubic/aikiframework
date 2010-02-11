<?php

/*
 * Aiki framework (PHP)
 *
 * @author		http://www.aikilab.com
 * @license		http://www.fsf.org/licensing/licenses/agpl-3.0.html
 * @link		http://www.aikiframework.org
 */

if(!defined('IN_AIKI')){die('No direct script access allowed');}


class aiki_xml
{
	var $timeout = 5; // set to zero for no timeout

	function rss_parser($text){
		global $aiki;

		$rss_matchs = preg_match_all('/\<rss\>(.*)\<\/rss\>/Us', $text, $matchs);

		if ($rss_matchs > 0){

			foreach ($matchs[1] as $rss){

				$rss_url = $aiki->get_string_between($rss , "<url>", "</url>");
				$rss_url = trim($rss_url);

				$limit = $aiki->get_string_between($rss , "<limit>", "</limit>");
				$limit = trim($limit);

				$output = $aiki->get_string_between($rss , "<output>", "</output>");

				if(!$output){

					$output = "<div class='news'>
						<h4>[[title]]</h4>
						<p>[[pubDate]]</p>
						<p><a href='[[link]]'>[[guid]]</a></p>
						<div class='description'>[[description]]</div>
						</div>";
				}

				$ch = curl_init();
				curl_setopt ($ch, CURLOPT_URL, $rss_url);
				curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);

				ob_start();
				curl_exec($ch);
				curl_close($ch);
				$content = ob_get_contents();
				ob_end_clean();


				if ($content !== false) {

					$xml = @simplexml_load_string($content);

					$i = 1;

					$html_output = '';
					if ($xml){
						foreach ($xml->channel->item as $item) {

							$items_matchs = preg_match_all('/\[\[(.*)\]\]/Us', $output, $elements);

							if ($items_matchs > 0){

								$processed_output = $output;

								foreach ($elements[1] as $element){

									$element = trim($element);

									$processed_output = str_replace("[[".$element."]]", $item->$element, $processed_output);

								}

								$html_output .= $processed_output;
								$processed_output = '';
							}



							if (isset($limit) and $limit == $i){
								break;
							}
							$i++;
						}

					}else{
						
						
					}
				}

				$text = str_replace("<rss>$rss</rss>", $html_output , $text);
			}
		}

		return $text;
	}

}


?>