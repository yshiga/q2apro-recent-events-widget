<?php

/*
	Plugin Name: Recent Events Widget
	Plugin URI: https://github.com/echteinfachtv/q2a-recent-events-widget
	Plugin Description: Displays the newest events of your q2a forum in a widget
	Plugin Version: 0.4
	Plugin Date: 2015-04-21
	Plugin Author: q2apro.com
	Plugin Author URI: http://www.q2apro.com/
	Plugin License: GPLv3
	Plugin Minimum Question2Answer Version: 1.5
	Plugin Update Check URI: https://raw.github.com/echteinfachtv/q2a-recent-events-widget/master/qa-plugin.php

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.gnu.org/licenses/gpl.html

*/

if ( !defined('QA_VERSION') )
{
	header('Location: ../../');
	exit;
}

// widget
qa_register_plugin_module('widget', 'qa-recent-events-widget.php', 'qa_recent_events_widget', 'Recent Events Widget');

// language file
qa_register_plugin_phrases('qa-recent-events-widget-lang-*.php', 'qa_recent_events_widget_lang');

// setting
qa_register_plugin_module('module', 'q2apro-recent-events-admin.php', 'q2apro_recent_events', 'q2apro Recent Event');

// page
qa_register_plugin_module('page', 'qa-recent-events-page.php', 'qa_recent_events_page', 'Recent Events Page');

// custom function to get all events and new events
function getAllForumEvents($queryRecentEvents, $eventsToShow, $region) {

	$maxEventsToShow = (int)(qa_opt('q2apro_recent_events_counts'));
	$listAllEvents = '';
	$countEvents = 0;

	while ( ($row = qa_db_read_one_assoc($queryRecentEvents,true)) !== null ) {
		if(in_array($row['event'], $eventsToShow)) {

			// question title
			$qTitle = '';

			// workaround: convert tab jumps to & to be able to use query function
			$toURL = str_replace("\t","&",$row['params']);
			// echo $toURL."<br />"; // we get e.g. parentid=4523&parent=array(65)&postid=4524&answer=array(40)
			parse_str($toURL, $data);  // parse URL to associative array $data
			// now we can access the following variables in array $data if they exist in toURL

			$linkToPost = "-";

			// find out type, if Q set link directly, if A or C do query to get correct link
			$postid = (isset($data['postid'])) ? $data['postid'] : null;
			if($postid !== null) {
				$getPostType = qa_db_read_one_assoc( qa_db_query_sub("SELECT type,parentid FROM `^posts` WHERE `postid` = #", $postid) );
				$postType = $getPostType['type']; // type, and $getPostType[1] is parentid
				if($postType=="A") {
					$getQtitle = qa_db_read_one_value( qa_db_query_sub("SELECT title FROM `^posts` WHERE `postid` = #", $getPostType['parentid']), true );
					$qTitle = (isset($getQtitle)) ? $getQtitle : "";
					// get correct public URL
					$activity_url = qa_path_html(qa_q_request($getPostType['parentid'], $qTitle), null, qa_opt('site_url'), null, null);
					$linkToPost = $activity_url."?show=".$postid."#a".$postid;
				}
				else if($postType=="C") {
					// get question link from answer
					$getQlink = qa_db_read_one_assoc( qa_db_query_sub("SELECT parentid,type FROM `^posts` WHERE `postid` = #", $getPostType['parentid']) );
					$linkToQuestion = $getQlink['parentid'];
					if($getQlink['type']=="A") {
						$getQtitle = qa_db_read_one_value( qa_db_query_sub("SELECT title FROM `^posts` WHERE `postid` = #", $getQlink['parentid']), true );
						$qTitle = (isset($getQtitle)) ? $getQtitle : "";
						// get correct public URL
						$activity_url = qa_path_html(qa_q_request($linkToQuestion, $qTitle), null, qa_opt('site_url'), null, null);
						$linkToPost = $activity_url."?show=".$postid."#c".$postid;
					}
					else {
						// default: comment on question
						$getQtitle = qa_db_read_one_value( qa_db_query_sub("SELECT title FROM `^posts` WHERE `postid` = #", $getPostType['parentid']), true);
						$qTitle = (isset($getQtitle)) ? $getQtitle : "";
						// get correct public URL
						$activity_url = qa_path_html(qa_q_request($getPostType['parentid'], $qTitle), null, qa_opt('site_url'), null, null);
						$linkToPost = $activity_url."?show=".$postid."#c".$postid;
					}
				}
				// if question is hidden, do not show frontend!
				else if($postType=="Q_HIDDEN") {
					$qTitle = '';
				}
				else {
					// question has correct postid to link
					// $questionTitle = (isset($data['title'])) ? $data['title'] : "";
					$getQtitle = qa_db_read_one_value( qa_db_query_sub("SELECT title FROM `^posts` WHERE `postid` = #", $postid), true );
					$qTitle = (isset($getQtitle)) ? $getQtitle : "";
					// get correct public URL
					$activity_url = qa_path_html(qa_q_request($postid, $qTitle), null, qa_opt('site_url'), null, null);
					$linkToPost = $activity_url;
				}
			}

			$username = (is_null($row['handle'])) ? qa_lang_html('qa_recent_events_widget_lang/anonymous') : htmlspecialchars($row['handle']);
			$usernameLink = (is_null($row['handle'])) ? qa_lang_html('qa_recent_events_widget_lang/anonymous') : '<a target="_blank" class="qa-user-link" style="font-weight:normal;" href="'.qa_opt('site_url').'user/'.$row['handle'].'">'.htmlspecialchars($row['handle']).'</a>';

			// set event name and css class
			$eventName = '';
			$eventNameShort = '';
			if($row['event']=="q_post") {
				$eventName = qa_lang_html('qa_recent_events_widget_lang/new_question');
				$eventNameShort = qa_lang_html('qa_recent_events_widget_lang/new_question_abbr');
			}
			else if($row['event']=="a_post") {
				$eventName = qa_lang_html('qa_recent_events_widget_lang/new_answer');
				$eventNameShort = qa_lang_html('qa_recent_events_widget_lang/new_answer_abbr');
			}
			else if($row['event']=="c_post") {
				$eventName = qa_lang_html('qa_recent_events_widget_lang/new_comment');
				$eventNameShort = qa_lang_html('qa_recent_events_widget_lang/new_comment_abbr');
			}
			else if($row['event']=="a_select") {
				$eventName = qa_lang_html('qa_recent_events_widget_lang/new_bestanswer');
				$eventNameShort = qa_lang_html('qa_recent_events_widget_lang/new_bestanswer_abbr');
			}
			else if($row['event']=="u_register") {
				$eventName = qa_lang_html('qa_recent_events_widget_lang/new_user');
				$eventNameShort = qa_lang_html('qa_recent_events_widget_lang/new_user_abbr');
				$linkToPost = "/user/$username";
				$qTitle = $username." registered.";
			}

			$evTime = '';
			// absolute time
			if(qa_opt('q2apro_recent_events_time_format') === '0') {
				$evTime = substr($row['datetime'],11,5) . qa_lang_html('qa_recent_events_widget_lang/hour_indic'); // 17:23h
				// relative time
			} else {
				// display date as 'before x time'
				$diff = time() - strtotime($row['datetime']);
				if($diff<60){
					$evTime = $diff . qa_lang_html('qa_recent_events_widget_lang/sec');
				}else if($diff < 60*60){
					$evTime = (int)($diff/60)  .  qa_lang_html('qa_recent_events_widget_lang/min');
				}else if($diff < 60*60*24){
					$evTime = (int)($diff/(60*60))  .  qa_lang_html('qa_recent_events_widget_lang/hour');
				}else{
					$evTime = (int)($diff/(60*60*24))  .  qa_lang_html('qa_recent_events_widget_lang/day');
				}
				$evTime .= qa_lang_html('qa_recent_events_widget_lang/ago');
			}

			// if question title is empty, question got possibly deleted, do not show frontend!
			if($qTitle=='') {
				continue;
			}

			// widget output, e.g. <a href="#" title="Antwort von echteinfachtv">17:23h A: Terme lösen und auskl...</a>
			$qTitleShort = mb_substr($qTitle,0,22,'utf-8'); // shorten question title to 22 chars
			$qTitleShort2 = (strlen($qTitle)>80) ? htmlspecialchars(mb_substr($qTitle,0,80,'utf-8')) .'&hellip;' : htmlspecialchars($qTitle); // shorten question title
			if ($region=='side') {
				$listAllEvents .= '<a class="tipsify" href="'.$linkToPost.'" title="'.$eventName.' '.qa_lang_html('qa_recent_events_widget_lang/new_by').' '.$username.': '.htmlspecialchars($qTitle).'">';
				$listAllEvents .= $evTime.' '.$eventNameShort. qa_lang_html('qa_recent_events_widget_lang/new_by') . $username .':'  .htmlspecialchars($qTitleShort).'&hellip;</a>';
			}
			else {
				$listAllEvents .= '<a href="'.$linkToPost.'">';
				$listAllEvents .= $evTime.' '.$eventName.' '.qa_lang_html('qa_recent_events_widget_lang/new_by').' '.$username.': '.$qTitleShort2.'</a>';
			}
			$countEvents++;
			if($countEvents>=$maxEventsToShow) {
				break;
			}
		}
	}

	return $listAllEvents;
} // end function getAllForumEvents()


/*
	Omit PHP closing tag to help avoid accidental output
*/
