<?php


if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;   
}               

if (!defined('QA_LISTS_CSRF_ACTION')) {
	define('QA_LISTS_CSRF_ACTION', 'lists-manage');
}

qa_register_plugin_module('module', 'qa-lists-admin.php', 'qa_lists_admin', 'Question Lists');
qa_register_plugin_module('page', 'qa-lists.php', 'qa_lists_page', 'Question Lists Page');
qa_register_plugin_module('page', 'qa-lists-usage.php', 'qa_lists_usage', 'Question Lists usage Page');
qa_register_plugin_module('page', 'qa-lists-ajax-page.php', 'qa_lists_ajax_page', 'Question Lists AJAX Page');
qa_register_plugin_layer('qa-lists-layer.php', 'Question Lists Layer');
qa_register_plugin_overrides('qa-lists-overrides.php', 'Question Lists Override');
qa_register_plugin_phrases('qa-lists-lang-default.php', 'lists_lang');
qa_register_plugin_module('event', 'qa-favoritelist.php', 'my_favorite_event', 'My Favorite Event');
    

function qa_lists_id_to_name($listid, $userid)
{
	if ($userid) {
		$name = qa_db_read_one_value(
			qa_db_query_sub(
				"SELECT listname FROM ^userlists WHERE userid=# AND listid=#",
				$userid, (int)$listid
			),
			true
		);
		if ($name !== null && $name !== '') {
			return $name;
		}
	}
	return qa_opt("qa-lists-id-name" . (int)$listid);
}

function qa_lists_savelist($userid, $postid, $addlistids = [], $removelistids = [])
{
    if (!$postid) {
        return false;
    }

    //Handle adding post to lists — atomic INSERT … ON DUPLICATE KEY to avoid race conditions
    foreach ($addlistids as $listid) {
        $listid = (int) $listid;
        $listname = qa_lists_id_to_name($listid, $userid);

        qa_db_query_sub(
            "INSERT INTO ^userlists (userid, listid, listname, questionids)
             VALUES (#, #, $, #)
             ON DUPLICATE KEY UPDATE
                 questionids = IF(
                     FIND_IN_SET(#, questionids),
                     questionids,
                     IF(questionids IS NULL OR TRIM(questionids) = '',
                         #,
                         CONCAT(questionids, ',', #)
                     )
                 )",
            $userid, $listid, $listname, $postid,
            $postid, $postid, $postid
        );
    }

    //Handle removing post from lists — atomic UPDATE via parameterized REPLACE
    foreach ($removelistids as $listid) {
        $listid = (int) $listid;

        qa_db_query_sub(
            "UPDATE ^userlists
             SET questionids = TRIM(BOTH ',' FROM REPLACE(CONCAT(',', questionids, ','), CONCAT(',', #, ','), ','))
             WHERE userid = # AND listid = # AND FIND_IN_SET(#, questionids)",
            $postid, $userid, $listid, $postid
        );
    }
	
	//Update ^userquestionlists with all lists this question now belongs to
	$query = "SELECT listid 
		FROM ^userlists 
		WHERE userid = # 
		  AND FIND_IN_SET(#, questionids)
	";
	$result = qa_db_query_sub($query, $userid, $postid);

	$currentLists = [];
	$rows = qa_db_read_all_assoc($result);

	foreach ($rows as $row) {
		$currentLists[] = $row['listid'];
	}

    $mylistids = implode(",", $currentLists);

	if (strlen($mylistids) === 0) {
		// No lists left → remove record
		qa_db_query_sub(
			"DELETE FROM ^userquestionlists
			 WHERE userid = #
			 AND questionid = #",
			$userid, $postid
		);
	} else {
		qa_db_query_sub(
			"INSERT INTO ^userquestionlists (userid, questionid, listids)
			 VALUES (#, #, $)
			 ON DUPLICATE KEY UPDATE listids = $",
			$userid, $postid, $mylistids, $mylistids
		);
	}
	
    return true;
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


