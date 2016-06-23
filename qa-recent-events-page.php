<?php

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
					'request' => 'recent-events-page',
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}


		function match_request($request)
		{
			if ($request=='recent-events-page')
				return true;

			return false;
		}


		function process_request($request)
		{
			$qa_content=qa_content_prepare();

			$qa_content['title']=qa_lang_html('example_page/page_title');
			$qa_content['error']='An example error';
			$qa_content['custom']='Some <b>custom html</b>';

			$qa_content['form']=array(
				'tags' => 'method="post" action="'.qa_self_html().'"',

				'style' => 'wide',

				'ok' => qa_post_text('okthen') ? 'You clicked OK then!' : null,

				'title' => 'Form title',

				'fields' => array(
					'request' => array(
						'label' => 'The request',
						'tags' => 'name="request"',
						'value' => qa_html($request),
						'error' => qa_html('Another error'),
					),

				),

				'buttons' => array(
					'ok' => array(
						'tags' => 'name="okthen"',
						'label' => 'OK then',
						'value' => '1',
					),
				),

				'hidden' => array(
					'hiddenfield' => '1',
				),
			);

			$qa_content['custom_2']='<p><br>More <i>custom html</i></p>';

			return $qa_content;
		}

	}


/*
	Omit PHP closing tag to help avoid accidental output
*/
