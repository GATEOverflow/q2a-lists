<?php

class qa_html_theme_layer extends qa_html_theme_base {


	function head_script()
	{
		qa_html_theme_base::head_script();
		if(qa_is_logged_in() )//&& $this->template=='questions')
		{

			if(qa_get_site_theme() !== "Donut")
			{
				$this->output(
					"	<script type='text/javascript'>
					$(document).ready(function(){
						$('#dropdown').click(function() {
							$(this).toggleClass('dropdown');
							$(this).toggleClass('dropdown-open');
			});
			});
</script>
"
					);
				}

		if ($this->template ==='question')
		{
			$this->output('
<script>
					var listsAjaxURL = "'.qa_path('ajaxlists').'";
					var listsAjaxURL = "'.qa_path('ajaxlists').'";
					var listsQuestionid = '.$this->content['q_view']['raw']['postid'].';
					</script>
					');

			$this->output('
					<script type="text/javascript" src="'.QA_HTML_THEME_LAYER_URLTOROOT.'script.js?v=0.00192"></script>
					<link rel="stylesheet" type="text/css" href="'.QA_HTML_THEME_LAYER_URLTOROOT.'styles.css?v=0.0002">
					');
		}
		}
	} // end head_script



	function doctype(){
		global $qa_request;
		$request = qa_request_parts();
		$requesti = $request[0];

		if($requesti == 'userlists')
		{
			if(!isset($request[1]))
			{
				qa_redirect('userlists/'.qa_get_logged_in_handle());
			}
		}
		qa_html_theme_base::doctype();

	}

	public function header()
	{
		$request = qa_request_parts();
		$request = $request[0];

		if($request === 'userlists')
		{
			if(qa_is_logged_in())
			{
	//		if(qa_get_site_theme() == "Donut")
				$listarray = array();
				$userid = qa_get_logged_in_userid();
				$query = "select distinct  listid as listid, listname from ^userlists order by listid";
				$result = qa_db_query_sub($query);
				$result = qa_db_read_all_assoc($result);
				if(true)
				{
					
		/*		$this->output('
						<div id="dropdown" class="dropdown">
						<button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">Question Lists
						<span class="caret"></span></button>
						<ul class="dropdown-menu">');*/
				$query = $_GET;
				$selected = @$query['listid'];
				$url = strtok(qa_self_html(), '?');
				for($i = 0; $i < count($result); $i++)
				{
					$query['listid'] = $result[$i]['listid'];
					$queryval = http_build_query($query);
					$clist = array(
						'label' => $result[$i]['listname'],
						'url' => $url.'?'.$queryval,
						'selected' => ($result[$i]['listid'] == $selected)
					);
					$listarray [] = $clist;
		//			$this-> output('<li><a href="'.$url.'?'.$queryval.'">'.$result[$i]['listname'].'</a></li>');
				}
				if(!$selected) $listarray[0]['selected'] = true;
		/*		$this->output('</ul>
						</div>');*/
				}


			}
			$this->content['navigation']['sub']= $listarray;
		       /*	array(
					'label' => qa_lang_html('qa_tupm_lang/subnav_title'),
					'url' => qa_path_html('topusers'),
					'selected' => ($qa_request == 'topusers')

		       );*/

		}
		//else
		qa_html_theme_base::header();
	}

	public function q_view_buttons($q_view)
	{
		if($this -> template == 'question')
		{
		if(qa_is_logged_in()  && isset($q_view['raw']['postid']))
		{
			$q_view['form']['buttons']['lists'] = array("tags" => 'data-postid="'.$q_view['raw']['postid'].'"  id="qa-userlists"', "label" => qa_lang_html('lists_lang/lists'), "popup" => "Add to a list");

		}
		}
		qa_html_theme_base::q_view_buttons($q_view);

	}
	public function q_view_main($q_view)
	{
		if(qa_is_logged_in() && $this->template=="question")
		{
			$userid = qa_get_logged_in_userid();
			$postid = qa_request_parts(0);
			$postid=$postid[0];
			$query = "select listids from ^userquestionlists where userid = # and questionid = #";
			$result = qa_db_query_sub($query, $userid, $postid);
			$listids = qa_db_read_one_value($result, true);
			$lists = explode(",", $listids);
			if(in_array("1", $lists)) $c1 = " checked"; else $c1 = '';
			if(in_array("2", $lists)) $c2 = " checked"; else $c2 = '';
			if(in_array("3", $lists)) $c3 = " checked"; else $c3 = '';
			if(in_array("4", $lists)) $c4 = " checked"; else $c4 = '';
			if(in_array("5", $lists)) $c5 = " checked"; else $c5 = '';
			if(in_array("6", $lists)) $c6 = " checked"; else $c6 = '';
			$this->output('
					<div id="qa-lists-popup">
					<div id="qa-lists-center">
					<div class="qa-lists-wrap">
					<h4>
					'.qa_lang('lists_lang/listnames').'
					</h4>
					<label>
					<input type="checkbox" name="qa-lists-check" value="1" '. $c1. ' >
					<span>'.qa_lists_id_to_name(1, $userid).'</span>
					</label>
					<label>
					<input type="checkbox" name="qa-lists-check" value="2" '. $c2. ' >
					<span>'.qa_lists_id_to_name(2, $userid).'</span>
					</label>
					<label>
					<input type="checkbox" name="qa-lists-check" value="3" '. $c3. ' >
					<span>'.qa_lists_id_to_name(3, $userid).'</span>
					</label>
					<label>
					<input type="checkbox" name="qa-lists-check" value="4" '. $c4 .' >
					<span>'.qa_lists_id_to_name(4, $userid).'</span>
					</label>
					<label>
					<input type="checkbox" name="qa-lists-check" value="5" '. $c5 .' >
					<span>'.qa_lists_id_to_name(5, $userid).'</span>
					</label>
					<label>
					<input type="checkbox" name="qa-lists-check" value="6" '. $c6 .' >
					<span>'.qa_lists_id_to_name(6, $userid).'</span>
					</label>

					<input type="button" class="qa-gray-button qa-go-list-send-button" value="'.qa_lang('q2apro_flagreasons_lang/send').'">

					<div class="closer">×</div>
					</div> </div></div>
					');
		}

		// default method call outputs the form buttons
		qa_html_theme_base::q_view_main($q_view);
	} // END function body_hidden()


}

