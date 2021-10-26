<?php
class qa_lists_admin {

	function option_default($option) {

		switch($option) {
			case 'qa_lists_level': 
				return QA_USER_LEVEL_MODERATOR;
			case 'qa-lists-id-name1': 
				return 'Important';
			case 'qa-lists-id-name2': 
				return 'Difficult';
			case 'qa-lists-id-name3': 
				return 'See Later';
			case 'qa-lists-id-name4': 
				return 'Wrongly Attempted';
			case 'qa-lists-id-name5': 
				return 'Custom List';
			case 'qa-lists-id-name6': 
				return 'Not Attempted in Exam';
			default:
				return null;				
		}

	}
	function init_queries($tableslc) {
		require_once QA_INCLUDE_DIR."db/selects.php";
		$queries = array();
		$tablename=qa_db_add_table_prefix('userlists');
		$usertablename=qa_db_add_table_prefix('users');
		$posttablename=qa_db_add_table_prefix('posts');
		if(!in_array($tablename, $tableslc)) {
			$queries[] = "
				CREATE TABLE `$tablename` (
						`userid` int(10) unsigned NOT NULL,
						`listid` smallint(5) NOT NULL,
						`questionids` mediumtext,
						`listname` varchar(40) DEFAULT NULL,
						PRIMARY KEY (`userid`,`listid`),
						FOREIGN KEY(`userid`) REFERENCES `$usertablename` (`userid`) ON DELETE CASCADE
						)";

		}
		$tablename=qa_db_add_table_prefix('userquestionlists');
		if(!in_array($tablename, $tableslc)) {
			$queries[] = "
				CREATE TABLE `$tablename` (
						`userid` int(10) unsigned NOT NULL,
						`questionid` int(10) unsigned NOT NULL,
						  `listids` varchar(255) DEFAULT NULL,
						  PRIMARY KEY (`userid`,`questionid`),
						FOREIGN KEY(`userid`) REFERENCES `$usertablename` (`userid`) ON DELETE CASCADE,
						FOREIGN KEY(`questionid`) REFERENCES `$posttablename` (`postid`) ON DELETE CASCADE
						)";

		}
		return $queries;
	} 

	function allow_template($template)
	{
		return ($template!='admin');
	}       

	function admin_form(&$qa_content)
	{                       

		// Process form input

		$ok = null;

		if (qa_clicked('qa_lists_save')) {
			qa_opt('qa_lists_level',qa_post_text('qa_lists_level'));
			qa_opt('qa-lists-id-name1',qa_post_text('qa-lists-id-name1'));
			qa_opt('qa-lists-id-name2',qa_post_text('qa-lists-id-name2'));
			qa_opt('qa-lists-id-name3',qa_post_text('qa-lists-id-name3'));
			qa_opt('qa-lists-id-name4',qa_post_text('qa-lists-id-name4'));
			qa_opt('qa-lists-id-name5',qa_post_text('qa-lists-id-name5'));
			qa_opt('qa-lists-id-name6',qa_post_text('qa-lists-id-name6'));
			$ok = qa_lang('admin/options_saved');
		}
		$showoptions = array(
				QA_USER_LEVEL_EXPERT => "Experts",
				QA_USER_LEVEL_EDITOR => "Editors",
				QA_USER_LEVEL_MODERATOR =>      "Moderators",
				QA_USER_LEVEL_ADMIN =>  "Admins",
				QA_USER_LEVEL_SUPER =>  "Super Admins",
				);

		// Create the form for display

		$fields = array();
		$fields[] = array(
				'label' => 'Min. User Level Required for Creating Lists',
				'tags' => 'name="qa_lists_level"',
				'value' => @$showoptions[qa_opt('qa_lists_level')],
				'type' => 'select',
				'options' => $showoptions,
				);
		$fields[] = array(
				'label' => 'List 1 Name',
				'tags' => 'name="qa-lists-id-name1"',
				'value' => qa_opt('qa-lists-id-name1'),
				'type' => 'text',
				);
		$fields[] = array(
				'label' => 'List 2 Name',
				'tags' => 'name="qa-lists-id-name2"',
				'value' => qa_opt('qa-lists-id-name2'),
				'type' => 'text',
				);
		$fields[] = array(
				'label' => 'List 3 Name',
				'tags' => 'name="qa-lists-id-name3"',
				'value' => qa_opt('qa-lists-id-name3'),
				'type' => 'text',
				);
		$fields[] = array(
				'label' => 'List 4 Name',
				'tags' => 'name="qa-lists-id-name4"',
				'value' => qa_opt('qa-lists-id-name4'),
				'type' => 'text',
				);
		$fields[] = array(
				'label' => 'List 5 Name',
				'tags' => 'name="qa-lists-id-name5"',
				'value' => qa_opt('qa-lists-id-name5'),
				//	'type' => 'text',
				);
		$fields[] = array(
				'label' => 'List 6 Name',
				'tags' => 'name="qa-lists-id-name6"',
				'value' => qa_opt('qa-lists-id-name6'),
				'type' => 'text',
				);
		return array(           
				'ok' => ($ok && !isset($error)) ? $ok : null,

				'fields' => $fields,

				'buttons' => array(
					array(
						'label' => qa_lang_html('main/save_button'),
						'tags' => 'NAME="qa_lists_save"',
					     ),
					),
			    );
	}
}

