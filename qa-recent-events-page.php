<?php
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

class qa_recent_events_page {

	const SHOW_EVENT = array('q_post', 'a_post', 'c_post', 'a_select', 'qas_blog_b_post', 'qas_blog_c_post');

	var $directory;
	var $urltoroot;

	function load_module($directory, $urltoroot)
	{
		$this->directory=$directory;
		$this->urltoroot=$urltoroot;
	}

	function suggest_requests() // for display in admin interface
	{
		return array(
			array(
				'title' => 'Recent Events',
				'request' => 'recent-events',
				'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
			),
		);
	}

	function match_request($request)
	{
		if ($request=='recent-events') {
			return true;
		}

		return false;
	}

	function process_request($request)
	{
		$per_page = 50;		// number of post per page
		$create_day = date("Y-m-d H:i:s", strtotime('-3 day'));	// how many days?
		$params =  self::SHOW_EVENT;
		$params []= $create_day;

		$start = qa_get_start();
		$total = $this->events_total($params);
		$qa_content=qa_content_prepare();

		$qa_content['title'] = qa_lang_html('qa_recent_events_widget_lang/page_title');
		$count = $per_page * ($start + 1);
		if ($count <= $total) {
			$count_str = $start + 1 . ' ～ ' . $count . '件 ( ' . $total . '件中 )';
		} else {
			$count_str = $start + 1 . ' ～ ' . $total . '件 ( ' . $total . '件中 )';
		}
		$qa_content['custom'] = '<div style="text-align:right;"><span style="color:gray">'.$count_str.'</span></div>';
		$qa_content['custom'] .= $this->get_recent_events($start, $per_page, $params);
		$qa_content['page_links']=qa_html_page_links(qa_request(), $start, $per_page, $total, qa_opt('pages_prev_next'));
		$qa_content['custom_2'] = '<DIV style="clear:both;"></DIV>';
		return $qa_content;
	}


	function get_events_html($queryRecentEvents, $eventsToShow)
	{
		$html = '';
		$events = qa_db_read_all_assoc($queryRecentEvents);
		if (count($events) <= 0) {
			return $html;
		}

		$html = '<div class="recent-events-container">';
		$html .= $this->create_html($events);
		$html .= '</div>';
		return $html;
	}

	function create_html($events){

		$html = '<ul>';
		foreach ($events as $row) {
			if (!in_array($row['event'], self::SHOW_EVENT)) {
				continue;
			}

			// workaround: convert tab jumps to & to be able to use query function
			$toURL = str_replace("\t","&",$row['params']);
			// echo $toURL."<br />"; // we get e.g. parentid=4523&parent=array(65)&postid=4524&answer=array(40)
			parse_str($toURL, $data);  // parse URL to associative array $data
			// now we can access the following variables in array $data if they exist in toURL

			$tmp = $this->get_event_info($data, $row['event']);
			$qTitle = $tmp[0];
			$eventContent = $tmp[1];
			$linkToPost = $tmp[2];

			$username = (is_null($row['handle'])) ? qa_lang_html('qa_recent_events_widget_lang/anonymous') : htmlspecialchars($row['handle']);
			$usernameLink = (is_null($row['handle'])) ? qa_lang_html('qa_recent_events_widget_lang/anonymous') : '<a target="_blank" class="qa-user-link" style="font-weight:normal;" href="'.qa_opt('site_url').'user/'.$row['handle'].'">'.htmlspecialchars($row['handle']).'</a>';

			// set event name and css class
			$eventName = $this->get_event_name($row['event']);
			$evTime = $this->get_event_time($row['datetime']);

			// if question title is empty, question got possibly deleted, do not show frontend!
			if($qTitle=='') {
				continue;
			}

			// widget output, e.g. <a href="#" title="Antwort von echteinfachtv">17:23h A: Terme lösen und auskl...</a>
			$qTitleShort = mb_substr($qTitle,0,22,'utf-8'); // shorten question title to 22 chars
			$qTitleShort2 = (strlen($qTitle)>80) ? htmlspecialchars(mb_substr($qTitle,0,80,'utf-8')) .'&hellip;' : htmlspecialchars($qTitle); // shorten question title

			$html .= $this->create_event_item_html($linkToPost, $qTitleShort2, $eventContent, $evTime, $eventName, $usernameLink);
		}
		$html .= '</ul>';
		return $html;
	}

	function create_event_item_html($linkToPost, $title, $content, $evTime, $eventName, $usernameLink){
			$html = '';
			$html .= '<li>';
			$html .= '<div class="event-item-title">';
			$html .= '<h3 class="mdl-layout-title"><a href="'.$linkToPost.'">' . $title .'</a></h3>';
			$html .= '</div>';
			$html .= '<span class="event-item-content">';
			$html .= $content;
			$html .= '</span>';
			$html .= '<br />';
			$html .= '<span class="event-item-meta">';
			$html .= $evTime.' '.$eventName.' '.qa_lang_html('qa_recent_events_widget_lang/new_by').' '.$usernameLink;
			$html .= '</span>';
			$html .= '</li>';

			return $html;
	}


	function get_event_time($event_time) {
		$evTime = '';
		// absolute time
		if(qa_opt('q2apro_recent_events_time_format') === '0') {
			$evTime = substr($event_time,11,5) . qa_lang_html('qa_recent_events_widget_lang/hour_indic'); // 17:23h
			// relative time
		} else {
			// display date as 'before x time'
			$diff = time() - strtotime($event_time);
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
		return $evTime;
	}


	/**
	 * イベントの情報を返す。
	 * @return Array  タイトル、詳細本文、リンクの配列を返す
	 */
	function get_event_info($data, $event) {
		if($event != 'qas_blog_b_post' && $event != 'qas_blog_c_post') {
			return $this->get_event_info_question($data);
		} else {
			return $this->get_event_info_blog($data);
		}
	}

	function get_event_info_blog($data) {
			// TODO ブログの場合も取得するようにしてください。
			return array('飼育日誌', 'コンテンツ', 'http://');
	}

	function get_event_info_question($data) {

		$linkToPost = "-";
		$content = "ここにイベントの本文が入ります。ここにイベントの本文が入ります。ここにイベントの本文が入ります。ここにイベントの本文が入ります。ここにイベントの本文が入ります。最大100文字";
		// find out type, if Q set link directly, if A or C do query to get correct link
		$postid = (isset($data['postid'])) ? $data['postid'] : null;
		if($postid !== null) {
			$getPostType = qa_db_read_one_assoc( qa_db_query_sub("SELECT type,parentid FROM `^posts` WHERE `postid` = #", $postid) );
			$postType = $getPostType['type']; // type, and $getPostType[1] is parentid
			if($postType=="A") {
				$getQtitle = qa_db_read_one_value( qa_db_query_sub("SELECT title, content FROM `^posts` WHERE `postid` = #", $getPostType['parentid']), true );
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
			} else if($postType=="Q_HIDDEN") {
				// if question is hidden, do not show frontend!
				$qTitle = '';
			} else {
				// question has correct postid to link
				// $questionTitle = (isset($data['title'])) ? $data['title'] : "";
				$getQtitle = qa_db_read_one_value( qa_db_query_sub("SELECT title FROM `^posts` WHERE `postid` = #", $postid), true );
				$qTitle = (isset($getQtitle)) ? $getQtitle : "";
				// get correct public URL
				$activity_url = qa_path_html(qa_q_request($postid, $qTitle), null, qa_opt('site_url'), null, null);
				$linkToPost = $activity_url;
			}
			return array($qTitle, $content, $linkToPost);
		}
	}

	function get_event_name($event_time) {
		if($event_time=="q_post") {
			$eventName = qa_lang_html('qa_recent_events_widget_lang/new_question');
		}
		else if($event_time=="a_post") {
			$eventName = qa_lang_html('qa_recent_events_widget_lang/new_answer');
		}
		else if($event_time=="c_post") {
			$eventName = qa_lang_html('qa_recent_events_widget_lang/new_comment');
		}
		else if($event_time=="a_select") {
			$eventName = qa_lang_html('qa_recent_events_widget_lang/new_bestanswer');
		}
		else if($event_time=="u_register") {
			$eventName = qa_lang_html('qa_recent_events_widget_lang/new_user');
			$linkToPost = $_SERVER['host']."index.php/user/$username";
			$qTitle = $username." registered.";
		}
		else if($event_time=="qas_blog_b_post") {
			$eventName = qa_lang_html('qa_recent_events_widget_lang/new_blog');
		}
		else if($event_time=="qas_blog_c_post") {
			$eventName = qa_lang_html('qa_recent_events_widget_lang/new_blog_comment');
		}
		return $eventName;
	}

	function get_recent_events($start = 0, $count = 50, $params)
	{
		array_push($params, $start);
		array_push($params, $count);
		$sql = "SELECT datetime,ipaddress,handle,event,params
				 FROM `^eventlog`
				 WHERE (`event` = $
				 OR `event` = $
				 OR `event` = $
				 OR `event` = $
				 OR `event` = $
				 OR `event` = $)
				 AND `datetime` >= $
				 ORDER BY datetime DESC
				 LIMIT #, #";

		$queryRecentEvents = qa_db_query_sub(qa_db_apply_sub($sql, $params));
		$eventsToShow = array_slice($params, 0, 6);
		return $this->get_events_html($queryRecentEvents, $eventsToShow);
	}

	function events_total($params)
	{
		// Get notifications total count for this user
		$sql  = "SELECT count(*)
				 FROM `^eventlog`
				 WHERE (`event` = $
				 OR `event` = $
				 OR `event` = $
				 OR `event` = $
				 OR `event` = $
				 OR `event` = $)
				 AND `datetime` >= $";

		return qa_db_read_one_value(qa_db_query_sub(qa_db_apply_sub($sql, $params)));
	}

}

/*
	Omit PHP closing tag to help avoid accidental output
*/
