<?php

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

return array(
	'lists' => 'Lists',
	'listnames' => 'Available Lists',
	'lists_pop' => 'Add to a Question List',
	'featured_qs_in_x' => 'Featured Questions in ^',
	'featured_qs_title' => 'Featured Questions',
	'nofeatured_qs_in_x' => 'No Featured Questions in ^',
	'nofeatured_qs_title' => 'No Featured Questions',
	'lists_title_with_list' => '^1 list of ^2',
	'lists_title_with_list_category' => "^3's ^2 list – ^1 questions",
	'lists_usage_title' => 'Lists Usage Dashboard',
	'lists_usage_active_date' => 'User last active within',


);
