<?php

if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
	header( 'Location: ../' );
	exit;
}
require_once QA_INCLUDE_DIR . 'db/selects.php';
require_once QA_INCLUDE_DIR . 'app/format.php';
require_once QA_INCLUDE_DIR . 'app/q-list.php';
$categoryslugs = qa_request_parts(2);
$countslugs = count($categoryslugs);

$sort = ($countslugs && !QA_ALLOW_UNINDEXED_QUERIES) ? null : qa_get('sort');
$start = qa_get_start();
$userid = qa_get_logged_in_userid();
$listid = qa_get('listid');
if($listid == NULL) $listid = 0;
// Get list of questions, plus category information

switch ($sort) {
        case 'hot':
                $selectsort = 'hotness';
                break;

        case 'votes':
                $selectsort = 'netvotes';
                break;

        case 'answers':
                $selectsort = 'acount';
                break;

        case 'views':
                $selectsort = 'views';
                break;

        default:
                $selectsort = 'created';
                break;
}
$selectspec =  qa_db_qs_mod_selectspec($userid, $selectsort, $start, $categoryslugs, null, false, false, qa_opt_if_loaded('page_size_qs'), $listid);
$query = "select questionids from ^userlists where userid = # and listid = #";
$result = qa_db_query_sub($query, $userid, $listid);
$tcount = qa_db_read_one_value($result, true);
if(!$tcount) $tcount = 0;
else $tcount = count(explode(",", $tcount));
//$selectspec['source'] .= "JOIN (select postid from ^posts where postid in (1,2,3,4,5))aby on aby.postid=^posts.postid";
list($questions, $categories, $categoryid) = qa_db_select_with_pending(
        $selectspec,
        qa_db_category_nav_selectspec($categoryslugs, false, false, true),
        $countslugs ? qa_db_slugs_to_category_id_selectspec($categoryslugs) : null
);

if ($countslugs) {
        if (!isset($categoryid)) {
                return include QA_INCLUDE_DIR . 'qa-page-not-found.php';
        }

        $categorytitlehtml = qa_html($categories[$categoryid]['title']);
        $nonetitle = qa_lang_html_sub('main/no_questions_in_x', $categorytitlehtml);

} else {
        $nonetitle = qa_lang_html('main/no_questions_found');
}


$categorypathprefix = QA_ALLOW_UNINDEXED_QUERIES ? 'questions/' : null; // this default is applied if sorted not by recent
$feedpathprefix = null;
$linkparams = array('listid' => $listid, 'sort' => $sort);
switch ($sort) {
        case 'hot':
                $sometitle = $countslugs ? qa_lang_html_sub('main/hot_qs_in_x', $categorytitlehtml) : qa_lang_html('main/hot_qs_title');
                $feedpathprefix = qa_opt('feed_for_hot') ? 'hot' : null;
                break;

        case 'votes':
                $sometitle = $countslugs ? qa_lang_html_sub('main/voted_qs_in_x', $categorytitlehtml) : qa_lang_html('main/voted_qs_title');
                break;

        case 'answers':
                $sometitle = $countslugs ? qa_lang_html_sub('main/answered_qs_in_x', $categorytitlehtml) : qa_lang_html('main/answered_qs_title');
                break;

        case 'views':
                $sometitle = $countslugs ? qa_lang_html_sub('main/viewed_qs_in_x', $categorytitlehtml) : qa_lang_html('main/viewed_qs_title');
                break;

        default:
                $linkparams = array('listid'=> $listid);
//$handle = qa_get_logged_in_handle();
$handle = qa_request_parts(1);
$handle=$handle[0];
                $sometitle = $countslugs ? qa_lang_html_sub('main/recent_qs_in_x', $categorytitlehtml) : qa_lang_html('main/recent_qs_title');
                $categorypathprefix = "userlists/$handle/";
                $feedpathprefix = qa_opt('feed_for_questions') ? 'questions' : null;
                break;
}
// Prepare and return content for theme

$qa_content = qa_q_list_page_content(
        $questions, // questions
        qa_opt('page_size_qs'), // questions per page
        $start, // start offset
        $tcount,//countslugs ? $categories[$categoryid]['qcount'] : qa_opt('cache_qcount'), // total count
        $sometitle, // title if some questions
        $nonetitle, // title if no questions
        $categories, // categories for navigation
        $categoryid, // selected category id
        false, // show question counts in category navigation
        $categorypathprefix, // prefix for links in category navigation
        $feedpathprefix, // prefix for RSS feed paths
        $countslugs ? qa_html_suggest_qs_tags(qa_using_tags()) : qa_html_suggest_ask($categoryid), // suggest what to do next
        $linkparams, // extra parameters for page links
        $linkparams // category nav params
);

if (QA_ALLOW_UNINDEXED_QUERIES || !$countslugs) {
        $qa_content['navigation']['sub'] = qa_qs_lists_sub_navigation($sort, $categoryslugs);
}


return $qa_content;


function qa_qs_lists_sub_navigation($sort, $categoryslugs)
{
        $request = 'userlists/'.qa_get_logged_in_handle();

        if (isset($categoryslugs)) {
                foreach ($categoryslugs as $slug) {
                        $request .= '/' . $slug;
                }
        }

        $navigation = array(
                'recent' => array(
                        'label' => qa_lang('main/nav_most_recent'),
                        'url' => qa_path_html($request),
                ),

                'hot' => array(
                        'label' => qa_lang('main/nav_hot'),
                        'url' => qa_path_html($request, array('sort' => 'hot')),
                ),

                'votes' => array(
                        'label' => qa_lang('main/nav_most_votes'),
                        'url' => qa_path_html($request, array('sort' => 'votes')),
                ),

                'answers' => array(
                        'label' => qa_lang('main/nav_most_answers'),
                        'url' => qa_path_html($request, array('sort' => 'answers')),
                ),

                'views' => array(
                        'label' => qa_lang('main/nav_most_views'),
                        'url' => qa_path_html($request, array('sort' => 'views')),
                ),
        );

        if (isset($navigation[$sort])) {
                $navigation[$sort]['selected'] = true;
        } else {
                $navigation['recent']['selected'] = true;
        }

        if (!qa_opt('do_count_q_views')) {
                unset($navigation['views']);
        }

        return $navigation;
}


function qa_db_qs_mod_selectspec($voteuserid, $sort, $start, $categoryslugs = null, $createip = null, $specialtype = false, $full = false, $count = null, $listid = 1)
{
        if ($specialtype == 'Q' || $specialtype == 'Q_QUEUED') {
                $type = $specialtype;
        } else {
                $type = $specialtype ? 'Q_HIDDEN' : 'Q'; // for backwards compatibility
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
		if($listid!=0){
		$query = "select questionids from ^userlists where userid=# and listid = #";
        $result = qa_db_query_sub($query, $voteuserid, $listid);
		$questions = qa_db_read_one_value($result, true);
		}
		else{
			$query = "select entityid from ^userfavorites where userid=# and entitytype = $";
			$result = qa_db_query_sub($query, $voteuserid, 'Q');
			$rows = qa_db_read_all_assoc($result);
			$favorite_questionids = array_column($rows, 'entityid');
			$questions = implode(',', $favorite_questionids);
		}
        
if(!$questions) $questions = "''";
$selectspec['source'] .= " JOIN (select postid from ^posts where postid in ($questions))aby on aby.postid=^posts.postid";
        $selectspec['source'] .=
                " JOIN (SELECT postid FROM ^posts WHERE " .
                qa_db_categoryslugs_sql_args($categoryslugs, $selectspec['arguments']) .
                (isset($createip) ? "createip=UNHEX($) AND " : "") .
        //      "type=$ " . $sortsql . " LIMIT #,#) y ON ^posts.postid=y.postid";//arjun
"type=$ ) y ON ^posts.postid=y.postid ".$sortsql." LIMIT #,#";
        if (isset($createip)) {
                $selectspec['arguments'][] = bin2hex(@inet_pton($createip));
        }

        array_push($selectspec['arguments'], $type, $start, $count);

        $selectspec['sortdesc'] = $sort;

        return $selectspec;
}

