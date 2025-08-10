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
    }
}