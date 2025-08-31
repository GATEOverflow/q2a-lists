<?php
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;   
}               


class my_favorite_event {
    function process_event($event, $userid, $handle, $cookieid, $params) {
        if ($event === 'q_favorite') {
            $postid = $params['postid'];
			qa_lists_savelist($userid, $postid, $addlistids = [0], $removelistids = []);
        }
        if ($event === 'q_unfavorite') {
            $postid = $params['postid'];
			qa_lists_savelist($userid, $postid, $addlistids = [], $removelistids = [0]);
        }
		if ($event === 'in_q_merge') {
			//in the event of merge of a question, we need to update the same in the lists
			$newpostid = (int)$params['postid'];
			$oldpostid = (int)$params['oldpostid'];
			$from_site    = $params['from_site_prefix'] ?? '';
			$to_site      = $params['to_site_prefix'] ?? '';
			$is_from_blog = (int)($params['is_from_blog'] ?? 0);
			$is_to_blog   = (int)($params['is_to_blog'] ?? 0);
			$oldRows = $params['oldpost_userquestionlists'] ?? []; //All the lists associated with the oldpostid for each user

			//Update the list when merging/only redirecting happened between Q->Q of same site
			if (!$is_from_blog && !$is_to_blog && ($from_site === $to_site)) {
				foreach ($oldRows as $oldRow) {
					$userid = (int)$oldRow['userid'];
					$listidsOfOld = $this->qa_lists_parse_listids($oldRow['listids']); // parse comma list to array
					// Get the NEW post's listids for this user
					$newRow = qa_db_read_one_assoc(qa_db_query_sub(
						"SELECT listids 
						 FROM ^userquestionlists 
						 WHERE userid = # 
						   AND questionid = #",
						$userid, $newpostid
					), true);

					$listidsOfNew = [];
					if (!empty($newRow) && !empty($newRow['listids'])) {
						$listidsOfNew = $this->qa_lists_parse_listids($newRow['listids']);
					}

					// Difference: old minus new
					$addlistids = array_values(array_diff($listidsOfOld, $listidsOfNew));

					// Only update if there’s something to add
					if (!empty($addlistids)) {
						qa_lists_savelist($userid, $newpostid, $addlistids, []); 
					}
				}
			}
        }
		if($event === 'q_delete'){
			$postid = $params['postid'];

			// Remove the postid from the comma-separated `questionids` list in qa_userlists
			qa_db_query_sub(
				"UPDATE ^userlists
				 SET questionids = TRIM(BOTH ',' FROM REPLACE(CONCAT(',', questionids, ','), ',$postid,', ','))
				 WHERE questionids LIKE '%,#,%' 
					OR questionids LIKE '#,%' 
					OR questionids LIKE '%,#' 
					OR questionids = '#'",
				$postid, $postid, $postid, $postid
			);
			
			//error_log("Removed postid $postid from all user lists due to deletion");
		}
    }
	
/*
 * Helper to parse comma-separated list IDs to an integer array.
 */
	function qa_lists_parse_listids($listidsStr) {
		$listidsStr = trim($listidsStr);
		if ($listidsStr === '') return [];
		$parts = explode(',', $listidsStr);
		$out = [];
		foreach ($parts as $p) {
			$p = trim($p);
			if ($p !== '' && ctype_digit($p)) {
				$out[] = (int)$p;
			}
		}
		return $out;
	}
}