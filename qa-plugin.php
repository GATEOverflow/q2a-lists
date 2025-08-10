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
qa_register_plugin_module('event', 'qa-favoritelist.php', 'my_favorite_event', 'My Favorite Event');
    

function qa_lists_id_to_name($listid, $userid)
{
	return qa_opt("qa-lists-id-name".$listid);
}

function qa_lists_savelist($userid, $postid, $addlistids = [], $removelistids = [])
{
    if (!$postid) {
        return false;
    }

    //Handle adding post to lists
    foreach ($addlistids as $listid) {
        $listid = (int) $listid;

        // Fetch existing question IDs for this list
        $result = qa_db_query_sub(
            "SELECT questionids FROM ^userlists WHERE userid = # AND listid = #",
            $userid, $listid
        );
        $questionids = qa_db_read_one_value($result, true);

        if ($questionids !== null && trim($questionids) !== '') {
            $questions = explode(",", trim($questionids));
            if (!in_array($postid, $questions)) {
                $questions[] = $postid;
            }
        } else {
            $questions = [$postid];
        }

        // Save back to userlists
        $questionsStr = trim(implode(",", array_filter($questions)));
        $listname = qa_lists_id_to_name($listid, $userid);

        qa_db_query_sub(
            "INSERT INTO ^userlists (userid, listid, listname, questionids)
             VALUES (#, #, $, $)
             ON DUPLICATE KEY UPDATE questionids = $",
            $userid, $listid, $listname, $questionsStr, $questionsStr
        );
    }

    //Handle removing post from lists
    foreach ($removelistids as $listid) {
        $listid = (int) $listid;

        // Remove from ^userlists
        $result = qa_db_query_sub(
            "SELECT questionids FROM ^userlists WHERE userid = # AND listid = #",
            $userid, $listid
        );
        $questionids = qa_db_read_one_value($result, true);

        if ($questionids !== null && trim($questionids) !== '') {
            $questions = explode(",", trim($questionids));
            if (in_array($postid, $questions)) {
                unset($questions[array_search($postid, $questions)]);
            }

            $questionsStr = trim(implode(",", array_filter($questions)));

                qa_db_query_sub(
                    "UPDATE ^userlists SET questionids = $ WHERE userid = # AND listid = #",
                    $questionsStr, $userid, $listid
                );
        }
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


