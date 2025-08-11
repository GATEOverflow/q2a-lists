<?php

class qa_lists_admin {
    public function option_default($option) {
        switch($option) {
            case 'qa_lists_level': 
                return QA_USER_LEVEL_MODERATOR;
            case 'qa-lists-count':
                return 5;
            case 'qa-lists-id-name0': return 'Favorites';
			case 'qa-lists-id-name1': return 'Important';
            case 'qa-lists-id-name2': return 'Difficult';
            case 'qa-lists-id-name3': return 'See Later';
            case 'qa-lists-id-name4': return 'Wrongly Attempted';
            case 'qa-lists-id-name5': return 'Custom List';
            case 'qa-lists-id-name6': return 'Not Attempted in Exam';
            case 'qa-lists-id-name7': return 'Revise before Exam';
            case 'qa-lists-id-name8': return 'Resources';
            case 'qa-lists-id-name9': return 'Need to Answer';
            case 'qa-lists-id-name10': return 'Watch List';
            default:
                return null;
        }
    }
	function init_queries($tableslc) {
		require_once QA_INCLUDE_DIR."db/selects.php";
		$queries = array();
		$tablename1=qa_db_add_table_prefix('userlists');
		$usertablename=qa_db_add_table_prefix('users');
		$posttablename=qa_db_add_table_prefix('posts');
		$tablename1_created = false;
		$tablename2_created = false;
		if(!in_array($tablename1, $tableslc)) {
			$queries[] = "
				CREATE TABLE `$tablename1` (
						`userid` int(10) unsigned NOT NULL,
						`listid` smallint(5) NOT NULL,
						`questionids` mediumtext,
						`listname` varchar(40) DEFAULT NULL,
						PRIMARY KEY (`userid`,`listid`),
						FOREIGN KEY(`userid`) REFERENCES `$usertablename` (`userid`) ON DELETE CASCADE
						)";
			$tablename1_created = true;

		}
		$tablename2=qa_db_add_table_prefix('userquestionlists');
		if(!in_array($tablename2, $tableslc)) {
			$queries[] = "
				CREATE TABLE `$tablename2` (
						`userid` int(10) unsigned NOT NULL,
						`questionid` int(10) unsigned NOT NULL,
						  `listids` varchar(255) DEFAULT NULL,
						  PRIMARY KEY (`userid`,`questionid`),
						FOREIGN KEY(`userid`) REFERENCES `$usertablename` (`userid`) ON DELETE CASCADE,
						FOREIGN KEY(`questionid`) REFERENCES `$posttablename` (`postid`) ON DELETE CASCADE
						)";
			$tablename2_created = true;
		}
	    // After creating tables, migrate favorites
		if ($tablename1_created && $tablename2_created) {
			$this->reset_favorites_list();
			error_log("checked");

		}
		return $queries;
	} 

	function allow_template($template)
	{
		return ($template!='admin');
	}
	
	public function reset_favorites_list()
	{
		// remove list 0 if exists
		$remove_from_userlists = qa_db_read_one_value(qa_db_query_sub('SHOW TABLES LIKE "qa_userlists"'), true) && qa_db_query_sub("DELETE FROM `qa_userlists` WHERE listid=0"); 
		$remove_from_userquestionlists = qa_db_read_one_value(qa_db_query_sub('SHOW TABLES LIKE "qa_userquestionlists"'), true) && qa_db_query_sub("UPDATE qa_userquestionlists
SET listids = TRIM(BOTH ',' FROM REPLACE(CONCAT(',', listids, ','), ',0,', ','))
WHERE listids LIKE '%0%'");

		// Get all favorite questions from Q2A core
		$favorites = qa_db_query_sub("
			SELECT uf.userid, uf.entityid AS questionid
			FROM ^userfavorites uf
			WHERE uf.entitytype = 'Q'
		");
		$rows = qa_db_read_all_assoc($favorites);
		foreach ($rows as $row) {	
			qa_lists_savelist(
				$row['userid'],
				$row['questionid'],
				array(0), // add to list 0
				array()   // remove none
			);
		}

	}

public function qa_lists_append_list($source_listid, $target_listid) {
    if ($source_listid === $target_listid) {
        return; // No point appending a list to itself
    }

    // Step 0: Get all users who have the source list (with questionids)
    $users_with_source = qa_db_read_all_assoc(
        qa_db_query_sub(
            "SELECT userid, questionids FROM ^userlists WHERE listid = # AND questionids != ''",
            $source_listid
        )
    );

    foreach ($users_with_source as $user_row) {
        $userid = $user_row['userid'];
        $source_qids = array_filter(explode(',', $user_row['questionids']));

        if (empty($source_qids)) {
            continue; // No questions for this user in source list
        }

        // Get target list question IDs for this user
        $target_row = qa_db_read_one_assoc(
            qa_db_query_sub(
                "SELECT questionids FROM ^userlists WHERE userid = # AND listid = #",
                $userid, $target_listid
            ),
            true
        );

        $target_qids = $target_row && !empty($target_row['questionids'])
            ? array_filter(explode(',', $target_row['questionids']))
            : [];

        // Merge without duplicates
        $new_target_qids = array_unique(array_merge($target_qids, $source_qids));
        sort($new_target_qids);

        // Insert or update the target list for this user
        qa_db_query_sub(
            "INSERT INTO ^userlists (userid, listid, questionids, listname) VALUES (#, #, $, $)
             ON DUPLICATE KEY UPDATE questionids = VALUES(questionids)",
            $userid, $target_listid, implode(',', $new_target_qids), qa_lists_id_to_name($target_listid, $userid),
        );

        // Update userquestionlists table for each question from source list
        foreach ($source_qids as $qid) {
            $uq_row = qa_db_read_one_assoc(
                qa_db_query_sub(
                    "SELECT listids FROM ^userquestionlists WHERE userid = # AND questionid = #",
                    $userid, $qid
                ),
                true
            );

            if ($uq_row) {
                $listids = array_filter(explode(',', $uq_row['listids']), 'strlen');

                if (!in_array($target_listid, $listids)) {
                    $listids[] = $target_listid;
                    sort($listids);
                    qa_db_query_sub(
                        "UPDATE ^userquestionlists SET listids = $ WHERE userid = # AND questionid = #",
                        implode(',', $listids), $userid, $qid
                    );
                }
            } else {
                // If entry doesn't exist, create it
                qa_db_query_sub(
                    "INSERT INTO ^userquestionlists (userid, questionid, listids) VALUES (#, #, $)",
                    $userid, $qid, $target_listid
                );
            }
        }
    }
}


	
    public function admin_form(&$qa_content)
    {
        $ok = null;
		$list_count = (int) qa_opt('qa-lists-count')? (int) qa_opt('qa-lists-count'): $this -> option_default('qa-lists-count');
		
		if (qa_clicked('qa_lists_migrate')) {
			$this->reset_favorites_list();
			$ok = 'Favorites list has been reset successfully.';
		}
		
		if (qa_clicked('qa_append_lists')) {
			$source_id = (int) qa_post_text('source_list');
			$destination_id = (int) qa_post_text('destination_list');
			if ($source_id === $destination_id) {
				$error = 'Source and destination lists cannot be the same.';
			} else {
				$this->qa_lists_append_list($source_id, $destination_id);
				$ok = 'List appended successfully.';
			}
		}

        // Handle save action
        if (qa_clicked('qa_lists_save')) {            
			$old_list_count = (int) qa_opt('qa-lists-count');
			$new_list_count = (int) qa_post_text('qa_lists_count');

			qa_opt('qa-lists-count', $new_list_count);

			for ($i = 0; $i <= $new_list_count; $i++) {
				qa_opt('qa-lists-id-name' . $i, qa_post_text('qa-lists-id-name' . $i));
			}

			if ($new_list_count < $old_list_count) {
				// Delete excess lists from DB
				$this->qa_lists_delete_excess_lists($new_list_count);
			}

			$ok = 'Lists settings saved';
		}

        // Build migrate form HTML
        $migrate_html = '<form method="post" action="">
            <input type="submit" name="qa_lists_migrate" class="qa-form-tall-button" value="Reset Favorites list">
        </form>';

		// Build append form HTML
		$append_html = '';
		$append_html .= '<label for="source_list"><strong>Source List:</strong></label> ';
		$append_html .= '<select id="source_list" name="source_list">';
		for ($i = 0; $i <= $list_count; $i++) {
			$append_html .= '<option value="' . $i . '">' . (qa_opt('qa-lists-id-name' . $i)? qa_opt('qa-lists-id-name' . $i): $this -> option_default('qa-lists-id-name' . $i)) . '</option>';
		}
		$append_html .= '</select><br><br>';
		$append_html .= '<label for="destination_list"><strong>Destination List:</strong></label> ';
		$append_html .= '<select id="destination_list" name="destination_list">';
		for ($i = 1; $i <= $list_count; $i++) {
			$append_html .= '<option value="' . $i . '">' . (qa_opt('qa-lists-id-name' . $i)? qa_opt('qa-lists-id-name' . $i): $this -> option_default('qa-lists-id-name' . $i)) . '</option>';
		}
		$append_html .= '</select><br><br>';
		$append_html .= '<input type="submit" name="qa_append_lists" class="qa-form-tall-button" value="Append Existing Lists">';

		//$append_html .= '</form>';



        // Add migrate and append as custom fields
        $fields[] = array(
            'type'  => 'custom',
            'label' => '<strong>Would you like to reset the Favorite list with the current Favorite questions?</strong>',
            'html'  => $migrate_html,
        );

        $fields[] = array(
            'type'  => 'custom',
            'label' => '<strong>Do you want to append all questions from one list to another? (Recommended before reducing the number of lists)</strong>',
            'html'  => $append_html,
        );
		
        // Dropdown for number of custom lists
		$list_count_options = '';
		for ($i = 1; $i <= 10; $i++) {
			$selected = ($i == $list_count) ? ' selected' : '';
			$list_count_options .= '<option value="' . $i . '"' . $selected . '>' . $i . '</option>';
		}

		$fields[] = array(
			'label' => 'Number of custom lists',
			'type'  => 'custom',
			'html'  => '<select name="qa_lists_count" id="qa_lists_count">' . $list_count_options . '</select>',
		);

		$i=0;
		$fields[] = array(
                'label' => 'List name ' . $i,
                'type'  => 'static',
                'tags'  => 'name="qa-lists-id-name' . $i . '"',
                'value' => qa_opt('qa-lists-id-name' . $i)? qa_opt('qa-lists-id-name' . $i): $this -> option_default('qa-lists-id-name' . $i),
		);
        for ($i = 1; $i <= $list_count; $i++) {
            $fields[] = array(
                'label' => 'List name ' . $i,
                'type'  => 'text',
                'tags'  => 'name="qa-lists-id-name' . $i . '"',
                'value' => qa_opt('qa-lists-id-name' . $i)? qa_opt('qa-lists-id-name' . $i): $this -> option_default('qa-lists-id-name' . $i),
            );
        }



        // Return admin form
        return array(
            'ok' => ($ok && !isset($error)) ? $ok : null,
            'fields' => $fields,
            'buttons' => array(
                array(
                    'label' => qa_lang_html('main/save_button'),
                    'tags'  => 'NAME="qa_lists_save" onclick="return confirm(\'Are you sure you want to save these settings?\nIf you reduce the number of lists, excess lists will be deleted.\nConsider appending questions from lists you want to keep before reducing.\')"',
                ),
            ),
        );
    }
	public function qa_lists_delete_excess_lists($new_count) {
		if (!is_int($new_count) || $new_count < 0) return;

		// Delete from userlists all lists with listid > new_count
		qa_db_query_sub(
			"DELETE FROM ^userlists WHERE listid > #",
			$new_count
		);

		// Clean listids in userquestionlists by removing list IDs > new_count
		$rows = qa_db_read_all_assoc(
			qa_db_query_sub("SELECT userid, questionid, listids FROM ^userquestionlists")
		);

		foreach ($rows as $row) {
			$listids = array_filter(explode(',', $row['listids']), 'strlen');
			$new_listids = array_filter($listids, function($lid) use ($new_count) {
				return ((int)$lid) <= $new_count;
			});

			if (count($listids) !== count($new_listids)) {
				qa_db_query_sub(
					"UPDATE ^userquestionlists SET listids = $ WHERE userid = # AND questionid = #",
					implode(',', $new_listids), $row['userid'], $row['questionid']
				);
			}
		}
	}

}