<?php

/*
 * Aikiframework
 *
 * @author		Bassel Khartabil
 * @copyright	Copyright (C) 2008-2009 Bassel Khartabil.
 * @license		http://www.gnu.org/licenses/gpl.html
 * @link		http://www.aikiframework.org
 */

header('Content-type: text/xml');

define('IN_AIKI', true);

require_once("../../../aiki.php");


if ($membership->permissions != "SystemGOD"){
	die("You do not have permissions to access this file");
}


echo '<?xml version="1.0" encoding="UTF-8"?>
<root>';


$get_urls = $db->get_results("select id, url from aiki_urls order by url,id");
$used = array();
foreach ($get_urls as $url){

	echo '<item parent_id="0" id="'.$url->url.'" ><content><name icon="'.$config['url'].'assets/images/icons/link.png"><![CDATA['.$url->url.']]></name></content></item>';

	$get_widgets = $db->get_results("select id, widget_name, father_widget, is_father from aiki_widgets where display_urls RLIKE '$url->url' order by father_widget,id");
	if($get_widgets){
		foreach ($get_widgets as $widget){

			if (!in_array($widget->id, $used)){
				if ($widget->father_widget == 0){
					$used[$widget->id] = $widget->id;
					echo '<item parent_id="'.$url->url.'" id="'.$widget->id.'" ><content><name icon="'.$config['url'].'assets/images/icons/layout_content.png"><![CDATA['.$widget->widget_name.']]></name></content></item>';
				}else{
					$used[$widget->id] = $widget->id;
					echo '<item parent_id="'.$widget->father_widget.'" id="'.$widget->id.'" ><content><name icon="'.$config['url'].'assets/images/icons/layout_content.png"><![CDATA['.$widget->widget_name.']]></name></content></item>';
				}
			}

		}
	}

}

echo "</root>";

?>