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
			$listids = $newdata['list'];
			//$listids = implode(",", $listids);

			$ajaxreturn = '';
			if(empty($questionid))
			{
				$reply = array( 'error' => "Data missing, received data is ".$newdata );
				echo json_encode($reply );
				return;
			}

			$userid = qa_get_logged_in_userid();		

			// *** should probably pass and check
			// qa_page_q_click_check_form_code($question, $error)

			$error = '';



			qa_lists_savelist($userid, $listids, $questionid);

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
