<?php
if (!defined('QA_VERSION')) {
    header('Location: ../../');
    exit;
}

class qa_lists_page
{
    private $userid;
    private $handle;
	private $listid;
    
	public function suggest_requests()
	{
		$guest_handle = qa_get_logged_in_handle();

		return [
			[
				'title' => qa_lang_html('lists_lang/All_notes'),
				'request' => 'userlists/' . $guest_handle . '/',
				'nav' => 'M',
			],
		];
	}

	public function match_request($request)
	{
		$requestparts = qa_request_parts();

		// Basic checks
		if ($requestparts[0] !== 'userlists') {
			return false;
		}

		$guest_handle = qa_get_logged_in_handle();
		$this->listid = isset($requestparts[2]) ? (int)$requestparts[2] : 0;

		// Not logged in → handled later via redirect
		if (!$guest_handle) {
			$this->userid = null;
			$this->handle = null;
			return true; // still return true, so process_request() can redirect
		}

		// Determine target user handle
		$target_handle = isset($requestparts[1]) ? $requestparts[1] : $guest_handle;
		$target_userid = qa_handle_to_userid($target_handle);

		if (!$target_userid) {
			// Non-existent user → handled by process_request
			$this->userid = null;
			$this->handle = $target_handle;
			return true;
		}

		$this->userid = $target_userid;
		$this->handle = $target_handle;

		return true;
	}


	public function process_request($request)
	{
		$guest_userid = qa_get_logged_in_userid();
		$guest_handle = qa_get_logged_in_handle();
		$requestparts = qa_request_parts();

		// User not logged in
		if (!$guest_userid) {
			qa_redirect('login', ['to' => qa_path(qa_request())]);
		}

		// Invalid or non-existent handle
		if (!$this->userid) {
			$qa_content = qa_content_prepare();
			$qa_content['error'] = 'No such user exists.';
			return $qa_content;
		}

		$isMy = ($this->handle === $guest_handle);
		$isAuthorized = qa_get_logged_in_level() >= QA_USER_LEVEL_ADMIN;

		// Check public access if viewing someone else's list
		if (!$isMy && !$isAuthorized) {
			$public = qa_db_read_one_value(
				qa_db_query_sub(
					'SELECT public FROM ^userlists WHERE userid=# AND listid=#',
					$this->userid,
					$this->listid
				),
				true
			);

			if ((int)$public !== 1) {
				$qa_content = qa_content_prepare();
				$qa_content['error'] = 'Accessing private list of another user is not allowed.';
				return $qa_content;
			}
		}
		$userid=$this->userid;
		// Load helpers
		require_once QA_INCLUDE_DIR . 'db/selects.php';
		require_once QA_INCLUDE_DIR . 'app/format.php';
		require_once QA_INCLUDE_DIR . 'app/q-list.php';

		// Pagination and sorting
		$categoryslugs = qa_request_parts(3);
		$countslugs = count($categoryslugs);
		$sort = ($countslugs && !QA_ALLOW_UNINDEXED_QUERIES) ? null : qa_get('sort');
		$start = qa_get_start();		
		$listid = $this->listid;
		
		// Determine sort
		$selectsort = match($sort) {
			'hot' => 'hotness',
			'votes' => 'netvotes',
			'answers' => 'acount',
			'views' => 'views',
			default => 'created',
		};

		// Build selectspec for user's notes
		$selectspec = $this->qa_db_qs_mod_selectspec(
			$userid, $selectsort, $start, $categoryslugs, null, false, false, qa_opt_if_loaded('page_size_qs'), $listid
		);

		// Build selectspec for categories based on user's notes
		$selectspec2 = $this->qa_db_category_nav_with_userlists_selectspec($categoryslugs, $userid,$listid);

		// Fetch questions with categories
		list($questions, $categories, $categoryid) = qa_db_select_with_pending(
			$selectspec,
			$selectspec2,
			$countslugs ? qa_db_slugs_to_category_id_selectspec($categoryslugs) : null
		);

		if (isset($categories[$categoryid])) {
			$total_questions = $categories[$categoryid]['questions_count'];
		} else {
			$total_questions = array_sum(array_column($categories, 'questions_count'));
		}


		// Fetch list name for current listid
		$listname = qa_db_read_one_value(
			qa_db_query_sub(
				'SELECT listname FROM ^userlists WHERE userid=# AND listid=#',
				$userid, $listid
			),
			true
		);

		// If not found in DB, fallback to qa_opt (default list definitions)
		if (!$listname) {
			$listname = qa_opt('qa-lists-id-name' . $listid);
		}
		$page_title = strtr(qa_lang_html('lists_lang/lists_title_with_list'), array(
					'^1' => qa_html($listname),
					'^2' => qa_html($this->handle),
				));
		// If browsing inside a category, adjust title
		if ($categoryid && isset($categories[$categoryid])) {
			$page_title = strtr(
				qa_lang_html('lists_lang/lists_title_with_list_category'),
				array(
					'^1' => qa_html($categories[$categoryid]['title']), // category
					'^2' => qa_html($listname),                        // list name
					'^3' => qa_html($this->handle),                    // user handle
				)
			);
		}
		$nonetitle = qa_lang_html_sub('main/no_questions_in_x',$page_title);

		$params = ['listid' => $listid, 'sort' => $sort];
				
		// Build basic question list content
		$qa_content = qa_q_list_page_content(
			$questions,
			qa_opt('page_size_qs'),
			$start,
			$total_questions,
			$page_title,
			$nonetitle,
			$categories,
			$categoryid ?? null,
			false,
			'userlists/'.$this->handle.'/'.$this->listid.'/', // category prefix
			null,
			null,
			$params,
			$params
		);
		return $qa_content;
	}

	private function qa_db_qs_mod_selectspec($voteuserid, $sort, $start, $categoryslugs = null, $createip = null, $specialtype = false, $full = false, $count = null, $listid=1)
	{
		if ($specialtype == 'Q' || $specialtype == 'Q_QUEUED') {
			$type = $specialtype;
		} else {
			$type = $specialtype ? 'Q_HIDDEN' : 'Q';
		}

		$count = isset($count) ? min($count, QA_DB_RETRIEVE_QS_AS) : QA_DB_RETRIEVE_QS_AS;

		switch ($sort) {
			case 'acount':
			case 'flagcount':
			case 'netvotes':
			case 'views':
				$sortsql = 'ORDER BY ^posts.' . $sort . ' DESC, ^posts.created DESC';
				break;

			case 'created':
			case 'hotness':
				$sortsql = 'ORDER BY ^posts.' . $sort . ' DESC';
				break;

			default:
				qa_fatal_error('qa_db_qs_selectspec() called with illegal sort value');
				break;
		}

		$selectspec = qa_db_posts_basic_selectspec($voteuserid, $full);
		// Get the user's postids from ^userreads and convert into CSV string
		$query = "select questionids from ^userlists where userid=# and listid = #";
		$result = qa_db_query_sub($query, $voteuserid,$listid);
		$questions = qa_db_read_one_value($result, true);
		if(!$questions) $questions = "''";

		// Append a JOIN that restricts posts to those in the user's reads
		$selectspec['source'] .= " JOIN (SELECT postid FROM ^posts WHERE postid IN ($questions)) aby ON aby.postid=^posts.postid";

		// Append the category slug filter + ordering + limit (keeps same structure as original)
		$selectspec['source'] .=
			" JOIN (SELECT postid FROM ^posts WHERE " .
			qa_db_categoryslugs_sql_args($categoryslugs, $selectspec['arguments']) .
			(isset($createip) ? "createip=UNHEX($) AND " : "") .
			"type=$ ) y ON ^posts.postid=y.postid " . $sortsql . " LIMIT #,#";

		if (isset($createip)) {
			$selectspec['arguments'][] = bin2hex(@inet_pton($createip));
		}

		array_push($selectspec['arguments'], $type, $start, $count);
		$selectspec['sortdesc'] = $sort;

		return $selectspec;
	}
	
	
	private function qa_db_category_nav_with_userlists_selectspec($categoryslugs, $userid, $listid, $full = true)
	{
		$selectspec = qa_db_category_nav_selectspec($categoryslugs, false, false, $full);

		// Add custom column for number of questions belonging to user's lists
		$selectspec['columns']['questions_count'] = 'COUNT(DISTINCT IF(FIND_IN_SET(^posts.postid, ^userlists.questionids), ^posts.postid, NULL))';

		// Replace GROUP BY with JOIN to qa_userlists
		$selectspec['source'] = str_replace(
			'GROUP BY ^categories.categoryid',
			'left JOIN ^posts 
				 ON ^posts.categoryid = ^categories.categoryid
				AND ^posts.type = \'Q\'
			 left JOIN ^userlists 
				 ON ^userlists.userid = ' . (int) $userid . '
				AND ^userlists.listid = ' . (int)$listid . '
				AND FIND_IN_SET(^posts.postid, ^userlists.questionids) > 0
			 GROUP BY ^categories.categoryid',
			$selectspec['source']
		);

		return $selectspec;
	}

}
