<?php
function qa_q_list_page_content($questions, $pagesize, $start, $count, $sometitle, $nonetitle,
		$navcategories, $categoryid, $categoryqcount, $categorypathprefix, $feedpathprefix, $suggest,
		$pagelinkparams=null, $categoryparams=null, $dummy=null)
{
	$requestParts = qa_request_parts();
	$request = isset($requestParts[0]) ? $requestParts[0] : '';
	$request = strtolower($request);

	if (($request === 'questions' || $request === 'unanswered') && (qa_get('sort') == 'featured')) {
		$pagelinkparams = array("sort" => "featured");
		$categorytitlehtml = qa_html($navcategories[$categoryid]['title']);
		$sometitle = $categoryid != null ? qa_lang_html_sub('featured_lang/featured_qs_in_x', $categorytitlehtml) : qa_lang_html('featured_lang/featured_qs_title');
		$nonetitle = $categoryid != null ? qa_lang_html_sub('featured_lang/nofeatured_qs_in_x', $categorytitlehtml) : qa_lang_html('featured_lang/nofeatured_qs_title');
		$feedpathprefix = null;

		if (!$categoryid) {
			$count = qa_opt('featured_qcount');
		} else {
			$count = qa_db_categorymeta_get($categoryid, 'fcount');
		}
	}


	return qa_q_list_page_content_base($questions, $pagesize, $start, $count, $sometitle, $nonetitle,
			$navcategories, $categoryid, $categoryqcount, $categorypathprefix, $feedpathprefix, $suggest,
			$pagelinkparams, $categoryparams, $dummy);
}


?>
