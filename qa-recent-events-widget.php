<?php
/*
	Question2Answer Plugin: Recent Events Widget
	Author: http://www.echteinfach.tv/
	License: http://www.gnu.org/licenses/gpl.html
*/

class qa_recent_events_widget {

	function allow_template($template)
	{
		$allow=false;

		switch ($template)
		{
			case 'qa':
			case 'questions':
				$allow=true;
				break;
		}

		return $allow;
	}

	function allow_region($region)
	{
		return true;
	}

	function output_widget($region, $place, $themeobject, $template, $request, $qa_content)
	{
		$themeobject->output('<a href="/recent-events"> >> 新着投稿をまとめて読む 質問5件、回答6件、飼育日誌2件、コメント14件 (24時間以内)</a>');
	}
} // end class

/*
	Omit PHP closing tag to help avoid accidental output
*/
