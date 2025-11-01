<?php

class qa_html_theme_layer extends qa_html_theme_base {


	function head_script()
	{
		qa_html_theme_base::head_script();
		if(qa_is_logged_in() )//&& $this->template=='questions')
		{

			if(qa_get_site_theme() !== "Donut")
			{
				$this->output(
					"	<script type='text/javascript'>
					$(document).ready(function(){
						$('#dropdown').click(function() {
							$(this).toggleClass('dropdown');
							$(this).toggleClass('dropdown-open');
			});
			});
</script>
"
					);
				}

		if ($this->template ==='question')
		{
			$this->output('
<script>
					var listsAjaxURL = "'.qa_path('ajaxlists').'";
					var listsAjaxURL = "'.qa_path('ajaxlists').'";
					var listsQuestionid = '.$this->content['q_view']['raw']['postid'].';
					</script>
					');

			$this->output('
					<script type="text/javascript" src="'.QA_HTML_THEME_LAYER_URLTOROOT.'script.js?v=0.00195"></script>
					<link rel="stylesheet" type="text/css" href="'.QA_HTML_THEME_LAYER_URLTOROOT.'styles.css?v=0.0002">
					');
		}
		}
	} // end head_script



	function doctype(){
		global $qa_request;
		$request = qa_request_parts();
		$requesti = $request[0];

		if($requesti == 'userlists')
		{
			if(!isset($request[1]))
			{
				qa_redirect('userlists/'.qa_get_logged_in_handle());
			}
		}
		qa_html_theme_base::doctype();

	}
	
	public function header()
	{
		$request = qa_request_part(0);

		if ($request === 'userlists' && qa_is_logged_in()) {

			$userid = qa_get_logged_in_userid();

			// Restricted (non-editable) list IDs
			$restricted_lists = [0, 4, 6];

			// Handle rename submission (from inline JS POST)
			if (qa_post_text('rename_listid') !== null) {
				$listid = (int) qa_post_text('rename_listid');
				$newname = trim(qa_post_text('new_listname'));

				if ($newname !== '' && !in_array($listid, $restricted_lists)) {
					qa_db_query_sub(
						'UPDATE ^userlists SET listname=$ WHERE userid=# AND listid=#',
						$newname, $userid, $listid
					);
				}
			}
			// Handle AJAX request for toggling public/private
			if (qa_post_text('toggle_public_listid') !== null) {
				$listid = (int) qa_post_text('toggle_public_listid');
				$userid = qa_get_logged_in_userid();

				// Read current visibility
				$public = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT public FROM ^userlists WHERE userid=# AND listid=#',
						$userid, $listid
					),
					true
				);

				if ($public === null) {
					echo json_encode(['error' => 'List not found']);
					qa_exit();
				}

				// Toggle (if 1 → 0, if 0 → 1)
				$new_public = ((int)$public === 1) ? 0 : 1;

				// Update DB
				qa_db_query_sub(
					'UPDATE ^userlists SET public=# WHERE userid=# AND listid=#',
					$new_public, $userid, $listid
				);

				echo json_encode([
					'success' => true,
					'listid' => $listid,
					'new_public' => $new_public
				]);
				qa_exit();
			}

			// Fetch user's lists
			$lists = qa_db_read_all_assoc(qa_db_query_sub(
				'SELECT listid, listname FROM ^userlists WHERE userid=# ORDER BY listid',
				$userid
			));

			// Get total list count (from admin setting)
			$list_count = (int) qa_opt('qa-lists-count');

			// Build a map for user-defined lists for quick lookup
			$user_lists = [];
			foreach ($lists as $l) {
				$user_lists[(int)$l['listid']] = $l['listname'];
			}

			// Always build full list set up to $list_count
			$final_lists = [];
			for ($i = 0; $i <= $list_count; $i++) {
				if (isset($user_lists[$i]) && $user_lists[$i] !== '') {
					// Use user's custom list name
					$final_lists[] = [
						'listid'   => $i,
						'listname' => $user_lists[$i],
					];
				} else {
					// Fallback to default list name from qa_opt
					$default_name = qa_opt('qa-lists-id-name' . $i);
					if ($default_name !== null && $default_name !== '') {
						$final_lists[] = [
							'listid'   => $i,
							'listname' => $default_name,
						];
					}
				}
			}

			// Replace $lists with merged result
			$lists = $final_lists;


			// Build sub-navigation
			$query = $_GET;
			$selected = qa_request_part(2) ?: 0;

			// Determine which handle to use
			$handle = qa_request_part(1) ?: qa_get_logged_in_handle();

			// Determine current category (if any)
			$categoryslugs = qa_request_parts(3); // e.g., ['theory-of-computation']
			$categorypath = $categoryslugs ? implode('/', $categoryslugs) : '';

			$nav_items = [];

			foreach ($lists as $l) {
				// Build base path: /userlists/{handle}/{listid}
				$listpath = 'userlists/' . $handle . '/' . $l['listid'];

				// Add category if it exists
				if ($categorypath) {
					$listpath .= '/' . $categorypath;
				}

				// Preserve query params (like sort)
				unset($query['listid'], $query['handle']);
				$queryval = http_build_query($query);
				$href = qa_path_html($listpath) . ($queryval ? '?' . $queryval : '');

				$nav_items['list_' . $l['listid']] = [
					'label' => qa_html($l['listname']),
					'url' => $href,
					'selected' => ((int)$l['listid'] === (int)$selected),
				];
			}

			// Default select first if nothing
			if (!$selected && count($nav_items)) {
				reset($nav_items);
				$firstKey = key($nav_items);
				$nav_items[$firstKey]['selected'] = true;
			}

			$this->content['navigation']['sub'] = $nav_items;

		}

			qa_html_theme_base::header();
	}

	public function q_view_buttons($q_view)
	{
		if($this -> template == 'question')
		{
			if(qa_is_logged_in()  && isset($q_view['raw']['postid']))
			{
				$q_view['form']['buttons']['lists'] = array("tags" => 'data-postid="'.$q_view['raw']['postid'].'"  id="qa-userlists"', "label" => qa_lang_html('lists_lang/lists'), "popup" => "Add to a list");

			}
		}
		qa_html_theme_base::q_view_buttons($q_view);
	}
	
	public function q_view_main($q_view)
	{
		if(qa_is_logged_in() && $this->template=="question")
		{
			$userid = qa_get_logged_in_userid();
			$postid = qa_request_parts(0);
			$postid=$postid[0];
			$query = "select listids from ^userquestionlists where userid = # and questionid = #";
			$result = qa_db_query_sub($query, $userid, $postid);
			$listids = qa_db_read_one_value($result, true);
			$lists = explode(",", $listids);
			$list_count = (int) qa_opt('qa-lists-count'); // total number of lists

			$this->output('<div id="qa-lists-popup">
				<div id="qa-lists-center">
				<div class="qa-lists-wrap">
				<h4>' . qa_lang('lists_lang/listnames') . '</h4>');
				
			for ($i = 0; $i <= $list_count; $i++) { 
				$checked = in_array((string)$i, $lists) ? ' checked' : '';
				$this->output('
					<label>
						<input type="checkbox" name="qa-lists-check" value="' . $i . '"' . $checked . '>
						<span>' . qa_lists_id_to_name($i, $userid) . '</span>
					</label>'
				);
			}

			$this->output('
				<input type="button" class="qa-gray-button qa-go-list-send-button" value="' . qa_lang('q2apro_flagreasons_lang/send') . '">
				<div class="closer">×</div>
				</div>
				</div>
				</div>');
		}

		// default method call outputs the form buttons
		qa_html_theme_base::q_view_main($q_view);
	} // END function body_hidden()


	public function body_suffix(){
		qa_html_theme_base::body_suffix();

		if (qa_request_part(0) === 'userlists') {
			$postUrl = qa_self_html();
			$userid = qa_get_logged_in_userid();
			$selected_list = (int) qa_request_part(2);

			// Fetch public flag for selected list
			$public = qa_db_read_one_value(
				qa_db_query_sub(
					'SELECT public FROM ^userlists WHERE userid=# AND listid=#',
					$userid,
					$selected_list
				),
				true
			);

			if ($public === null) {
				$public = 0; // fallback if not found
			}

			//Dynamically build restricted list IDs
			$restricted_lists = [];
			$list_count = (int) qa_opt('qa-lists-count');
			for ($i = 0; $i <= $list_count; $i++) {
				$is_public = (int) qa_opt('qa-lists-id-editable' . $i);
				if ($is_public === 0) {
					$restricted_lists[] = $i;
				}
			}
			$restricted_json = json_encode(array_fill_keys($restricted_lists, true));

			echo '<script>';
			?>
			jQuery(function ($) {
			  const restricted = <?php echo $restricted_json ?: '{}'; ?>;
			  const postUrl = <?php echo json_encode($postUrl); ?>;
			  const currentId = <?php echo (int) $selected_list; ?>;
			  const isPublicInitial = <?php echo (int) $public; ?>;

			  function getListIdFromHref(href) {
				if (!href) return null;
				const m = href.match(/\/userlists\/[^/]+\/(\d+)/);
				return m ? parseInt(m[1], 10) : null;
			  }

			  // Build edit + visibility icons
			  function ensureIconsForCurrent() {
				if (currentId === null || isNaN(currentId)) return;
				$(".list-edit-wrapper").remove();

				const target = $(".qa-nav-sub a, .qa-nav-sub-list a").filter(function () {
				  return getListIdFromHref($(this).attr("href")) === currentId;
				}).first();

				if (!target.length) return;

				// Make sure link and icons align
				target.css({
				  display: "inline-block",
				  verticalAlign: "middle",
				  marginRight: "4px"
				});

				const wrapper = $("<span/>", {
				  class: "list-edit-wrapper",
				  css: { display: "inline-block", verticalAlign: "middle" }
				});

				// ✏️ Rename (only for non-restricted)
				if (!restricted[currentId]) {
				  const editIcon = $("<span/>", {
					class: "list-edit-icon",
					title: "Rename list",
					text: "✏️",
					css: { cursor: "pointer", opacity: 0.8, marginRight: "6px" }
				  });
				  wrapper.append(editIcon);
				}

				// 👁 / 🔒 Visibility toggle (always shown)
				const viewIcon = $("<span/>", {
				  class: "list-view-icon",
				  text: isPublicInitial ? "🔒" : "👁",
				  title: isPublicInitial
					? "Public (click to make private)"
					: "Private (click to make public)",
				  css: { cursor: "pointer", opacity: 0.8 }
				}).data("public", isPublicInitial);

				wrapper.append(viewIcon);
				target.after(wrapper);
			  }

			  // Inline rename handler
			  $(document).on("click", ".list-edit-icon", function (e) {
				e.preventDefault();
				e.stopPropagation();

				const icon = $(this);
				const link = icon.closest(".list-edit-wrapper").prev("a");
				if (!link.length) return;

				const listid = getListIdFromHref(link.attr("href"));
				if (!listid) return;
				if (restricted[listid]) {
				  alert("This list cannot be renamed.");
				  return;
				}

				const current = $.trim(link.text());
				const input = $("<input/>", {
				  type: "text",
				  value: current,
				  class: "listname-input"
				}).css({
				  width: (Math.max(current.length, 2) + 2) + "ch",
				  display: "inline-block",
				  verticalAlign: "middle"
				});

				link.replaceWith(input);
				input.focus().select();

				function restore(name) {
				  const newLink = $("<a/>", { href: link.attr("href"), text: name });
				  newLink.css({
					display: "inline-block",
					verticalAlign: "middle",
					marginRight: "4px"
				  });
				  input.replaceWith(newLink);
				  newLink.after(icon.closest(".list-edit-wrapper"));
				}

				function save() {
				  const newname = $.trim(input.val());
				  if (!newname || newname === current) {
					restore(current);
					return;
				  }

				  $.post(postUrl, { rename_listid: listid, new_listname: newname, qa_click: 1 })
					.done(() => restore(newname))
					.fail(() => restore(current));
				}

				input.on("blur", save);
				input.on("keydown", (ev) => {
				  if (ev.key === "Enter") save();
				  if (ev.key === "Escape") restore(current);
				});
			  });

			  // 👁 / 🔒 Visibility toggle handler
			  $(document).on("click", ".list-view-icon", function (e) {
				e.preventDefault();
				e.stopPropagation();

				const icon = $(this);
				const link = icon.closest(".list-edit-wrapper").prev("a");
				if (!link.length) return;

				const listid = getListIdFromHref(link.attr("href"));
				if (!listid) return;

				const isPublic = icon.data("public") || 0;
				const msg = isPublic ? "Make this list private?" : "Make this list public?";
				if (!confirm(msg)) return;

				$.post(postUrl, {
				  toggle_public_listid: listid, // handled in PHP
				  qa_click: 1
				}).done(function (resp) {
				  let data;
				  try {
					data = typeof resp === "object" ? resp : JSON.parse(resp);
				  } catch (e) {}
				  const newState =
					data && typeof data.new_public !== "undefined"
					  ? data.new_public
					  : isPublic
					  ? 0
					  : 1;

				  icon.text(newState ? "🔒" : "👁");
				  icon.attr(
					"title",
					newState
					  ? "Public (click to make private)"
					  : "Private (click to make public)"
				  );
				  icon.data("public", newState);
				});
			  });

			  // Initialize once nav is ready
			  let tries = 0,
				t = setInterval(function () {
				  if ($(".qa-nav-sub a").length || tries++ > 15) {
					clearInterval(t);
					ensureIconsForCurrent();
				  }
				}, 150);
			});
			<?php
				echo '</script>';
		}
	}
}

