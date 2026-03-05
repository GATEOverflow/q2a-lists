<?php

class qa_lists_ajax_page
{

	var $directory;
	var $urltoroot;

	function load_module($directory, $urltoroot)
	{
		$this->directory = $directory;
		$this->urltoroot = $urltoroot;
	}


	// for url query
	function match_request($request)
	{
		if ($request=='ajaxlists') 
		{
			return true;
		}

		return false;
	}

	function process_request($request)
	{	

		// only logged in users
		if(!qa_is_logged_in())
		{
			exit();
		}


		// AJAX post: we received post data, so it should be the ajax call with flag data
		$transferString = qa_post_text('ajaxdata');

		if(!empty($transferString)) 
		{
			$newdata = json_decode($transferString, true);
			//$newdata = str_replace('&quot;', '"', $newdata); // see stackoverflow.com/questions/3110487/

			$questionid = (int)$newdata['questionid'];
			$addlistids = $newdata['addList'];
			$removelistids = $newdata['removeList'];
			$removeAll    = !empty($newdata['removeAll']);

			$ajaxreturn = '';
			if(empty($questionid))
			{
				$reply = array( 'error' => "Data missing, received data is ".$newdata );
				echo json_encode($reply );
				return;
			}

			$userid = qa_get_logged_in_userid();
			
			if ($removeAll) {
				// Fetch all list IDs this question belongs to for this user
				$result = qa_db_query_sub(
					"SELECT listid FROM ^userlists WHERE userid=# AND FIND_IN_SET(#, questionids)",
					$userid, $questionid
				);
				$rows = qa_db_read_all_assoc($result);
				$removelistids = array_column($rows, 'listid');
			}

			// *** should probably pass and check
			// qa_page_q_click_check_form_code($question, $error)

			$error = '';



			qa_lists_savelist($userid,$questionid,$addlistids,$removelistids);

			// If list 0 was removed, also unfavorite the question natively
			if (in_array(0, array_map('intval', $removelistids))) {
				qa_db_query_sub(
					"DELETE FROM ^userfavorites WHERE userid=# AND entitytype=$ AND entityid=#",
					$userid, 'Q', $questionid
				);
			
				// Fire the same event Q2A fires natively on unfavorite
				qa_report_event('q_unfavorite', $userid, qa_get_logged_in_handle(), qa_cookie_get(), [
					'postid' => $questionid,
				]);
			}

			if($error)
			{
				$reply = array(
						'error' => $error,
					      );
				echo json_encode( $reply );
				return;
			}

			$reply = array(
					'success' => '1',
				      );
			echo json_encode( $reply );
			return;

		} // END AJAX RETURN
		else 
		{
			echo 'Unexpected problem detected. No transfer string.';
			exit();
		}

		return $qa_content;
	} // end process_request

}; 
