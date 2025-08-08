<?php


if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;   
}               

qa_register_plugin_module('module', 'qa-lists-admin.php', 'qa_lists_admin', 'Question Lists');
qa_register_plugin_module('page', 'qa-lists-page.php', 'qa_lists_page', 'Question Lists Page');
qa_register_plugin_module('page', 'qa-lists-ajax-page.php', 'qa_lists_ajax_page', 'Question Lists AJAX Page');
qa_register_plugin_layer('qa-lists-layer.php', 'Question Lists Layer');
qa_register_plugin_overrides('qa-lists-overrides.php', 'Question Lists Override');
qa_register_plugin_phrases('qa-lists-lang-*.php', 'lists_lang');    

function qa_lists_id_to_name($listid, $userid)
{
	return qa_opt("qa-lists-id-name".$listid);
}

function qa_lists_savelist($userid, $listids, $postid)
{
	//if(!$listids || !$postid) return false;
	if(!$postid) return false;
	for($i = 0; $i < 6; $i++)	// there are six list except the favorite
	{
		//foreach($listids as $listid)
		//{
		$listid = $i+1;
		$query = "select questionids from ^userlists where userid=# and listid = #";
		$result = qa_db_query_sub($query, $userid, $listid);
		$questionids = qa_db_read_one_value($result, true);
		if(($questionids !== NULL) && (trim($questionids) !== ''))
		{
			//	file_put_contents("/tmp/outp.txt", $questionids."\t".$postid."\n", FILE_APPEND);
			$questions = explode(",", trim($questionids));
			if(!in_array($postid, $questions) && in_array($listid, $listids))
			{
				$questions[] = $postid;
			}
			else if(in_array($postid, $questions) && !in_array($listid, $listids))
			{
				$pos = array_search($postid, $questions);
				unset($questions[$pos]);
				//$questions = array_splice($questions, array_search($postid, $questions), 1);
			}

			$questions = trim(implode(",", array_filter($questions)));
		}
		else if(in_array($listid, $listids))
			$questions = $postid;
		else $questions = '';
		$listname = qa_lists_id_to_name($listid, $userid);	//userid not required
		$query = "insert into ^userlists(userid, listid, listname, questionids) values (#,#,$,$) on duplicate key update questionids = $";
		$result = qa_db_query_sub($query,   $userid,$listid, $listname, $questions, $questions);
		$query = "insert into ^userquestionlists(userid, questionid, listids) values (#,#,$) on duplicate key update listids = $";
		$mylistids = implode(",", array_filter($listids));
		$result = qa_db_query_sub($query, $userid,$postid, $mylistids,$mylistids);

	}
}
function qa_lists_save_questions($userid, $list_id, $postids)	//function defined but not used anywhere in the plugin.
{
	$postids = explode(",", trim($postids));
	foreach($postids as $postid)
	{
		if(trim($postid) == '') continue;
		$query = "select listids from ^userquestionlists where userid=# and questionid = #";
		$result = qa_db_query_sub($query, $userid, $postid);
		$listids = qa_db_read_one_value($result, true);
		if(($listids !== NULL) && (trim($listids) !== ''))
		{
			$lists = explode(",", trim($listids));
			if(!in_array($list_id, $lists))
			{
				$lists[] = $list_id;
			}
			$lists = trim(implode(",", array_filter($lists)));
		}
		$lists = $list_id;
		$query = "insert into ^userquestionlists(userid, questionid, listids) values (#,#,$) on duplicate key update listids = $";
		$result = qa_db_query_sub($query, $userid,$postid, $lists,$lists);
	}
	$listname = qa_lists_id_to_name($list_id, $userid);
	$query = "select questionids from ^userlists where userid=# and listid = #";
	$result = qa_db_query_sub($query, $userid, $list_id);
	// $query = "select entityid from ^userfavorites where userid=# and entitytype = $";
	// $result = qa_db_query_sub($query, $userid, 'Q');
	$questionids = qa_db_read_one_value($result, true);
	//if(count(@$questionids) > 0)
	if($questionids) 
	{
		$aquestionids = explode(",", trim($questionids));
		$questions = array_unique(array_merge($postids,$aquestionids));
	}
	else
		$questions = $postids;//array_unique(array_merge($postids,$aquestionids));
	$questions = implode(",", array_filter($questions));
	$query = "insert into ^userlists(userid, listid, listname, questionids) values (#,#,$,$) on duplicate key update questionids = $";
	$result = qa_db_query_sub($query,   $userid,$list_id, $listname, $questions, $questions);
}

/*                              
				Omit PHP closing tag to help avoid accidental output
 */                              


