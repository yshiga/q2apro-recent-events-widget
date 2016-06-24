<?php
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

class qa_recent_events_page {

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
		if ($request=='recent-events')
			return true;

		return false;
	}


	function process_request($request)
	{
		// 各条件を設定
		$per_page = 50;		// ページあたりの表示件数
		$create_day = date("Y-m-d H:i:s", strtotime('-30 day'));	// 何日分か
		$params = array('q_post', 'a_post', 'c_post', 'a_select', $create_day);	// 対象イベント

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

		// $qa_content['custom_2']='<p><br>More <i>custom html</i></p>';
		$qa_content['page_links']=qa_html_page_links(qa_request(), $start, $per_page, $total, qa_opt('pages_prev_next'));

		$qa_content['custom_2'] = '<DIV style="clear:both;"></DIV>';
		return $qa_content;
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
				 OR `event` = $)
				 AND `datetime` >= $
				 ORDER BY datetime DESC
				 LIMIT #, #";

		$queryRecentEvents = qa_db_query_sub(qa_db_apply_sub($sql, $params));
		$eventsToShow = array_slice($params, 0, 4);
		return $this->get_events_html($queryRecentEvents, $eventsToShow);
	}

	function get_events_html($queryRecentEvents, $eventsToShow)
	{
		$html = '';
		$events = qa_db_read_all_assoc($queryRecentEvents);

		if (count($events) <= 0) {
			return $html;
		}

		$html = '<div class="recent-events-container">';
		$html .= '<ul>';
		foreach ($events as $row) {
			if (!in_array($row['event'], $eventsToShow)) {
				continue;
			}

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
				$linkToPost = $_SERVER['host']."index.php/user/$username";
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

			$html .= '<li>';
			$html .= '<div class="event-item-title">';
			$html .= '<h3 style="margin: 10px 0 0 0;"><a href="'.$linkToPost.'">' . $qTitleShort2.'</a></h3>';
			$html .= '</div>';
			$html .= '<span class="event-item-meta">';
			$html .= $evTime.' '.$eventName.' '.qa_lang_html('qa_recent_events_widget_lang/new_by').' '.$username;
			$html .= '</span>';
			$html .= '</li>';

		}
		$html .= '</ul>';
		$html .= '</div>';
		return $html;
	}

	function events_total($params)
	{
		// Get notifications total count for this user
		$sql  = "SELECT count(*)
				 FROM `^eventlog`
				 WHERE (`event` = $
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
