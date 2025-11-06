<?php
class qa_lists_usage {

    public function match_request($request) {
        return $request == 'lists-usage';
    }

    public function process_request($request) {

        // --- Default filters ---
        $months = (int)(qa_get('months') ?: 6);
        $top_users = (int)(qa_get('limit') ?: 20);

        // --- Load system-defined list names + ensure all existing listids are included ---
        $lists = [];
        $listids = qa_db_read_all_assoc(qa_db_query_sub('SELECT DISTINCT listid FROM ^userlists ORDER BY listid ASC'));
        foreach ($listids as $l) {
            $i = (int)$l['listid'];
            $name = qa_opt('qa-lists-id-name' . $i);
            if (empty($name)) {
                $name = 'List ' . $i; // fallback name
            }
            $lists[] = [
                'listid' => $i,
                'listname' => $name,
            ];
        }

        // --- Load user list data (active users only) ---
        $rows = qa_db_read_all_assoc(qa_db_query_sub(
            'SELECT u.userid, u.handle, ul.listid,
                    (LENGTH(TRIM(BOTH \',\' FROM ul.questionids))
                     - LENGTH(REPLACE(TRIM(BOTH \',\' FROM ul.questionids), \',\', \'\')) + 1) AS question_count
             FROM ^userlists ul
             JOIN ^users u ON u.userid = ul.userid
             JOIN ^usermetas um ON um.userid = u.userid AND um.title = "lastactivedate"
             WHERE STR_TO_DATE(um.content, "%Y-%m-%d") >= DATE_SUB(CURDATE(), INTERVAL ' . (int)$months . ' MONTH)
             AND COALESCE(ul.questionids, "") <> ""'
        ));

        // --- Aggregate per user ---
        $userData = [];
        foreach ($rows as $r) {
            $uid = $r['userid'];
            if (!isset($userData[$uid])) {
                $userData[$uid] = [
                    'userid' => $uid,
                    'handle' => $r['handle'],
                    'lists'  => [],
                ];
            }
            $userData[$uid]['lists'][$r['listid']] = (int)$r['question_count'];
        }

        // --- Page header ---
        $content = qa_content_prepare();
        $content['title'] = qa_lang_html('lists_lang/lists_usage_title');

        // --- Filter controls ---
        $html = '<div class="filters">
            <label>' . qa_lang_html('lists_lang/lists_usage_active_date') . ':
                <select id="monthsSelect">
                    <option value="3" ' . ($months==3?'selected':'') . '>3 months</option>
                    <option value="6" ' . ($months==6?'selected':'') . '>6 months</option>
                    <option value="9" ' . ($months==9?'selected':'') . '>9 months</option>
                    <option value="12" ' . ($months==12?'selected':'') . '>12 months</option>
                </select>
            </label>

            <label>Top Users:
                <input type="number" id="limitInput" value="' . (int)$top_users . '" min="5" max="100">
            </label>

            <label>Select Lists:</label><br>
            <select id="listsSelect" multiple size="6" style="min-width:220px;">
                <option value="__all__" selected>(All Lists)</option>';
        foreach ($lists as $l)
            $html .= '<option value="' . (int)$l['listid'] . '">' . qa_html($l['listname']) . '</option>';
        $html .= '</select><br>
            <button id="applyFilter" class="qa-form-tall-button">Apply Filter</button>
        </div>';

        $html .= '<div id="leaderboardContainer"><div id="leaderboardTable"></div></div>';

        // --- JavaScript ---
        $html .= '<script>
            const allUsers = ' . json_encode(array_values($userData)) . ';
            const allLists = ' . json_encode($lists) . ';
            const defaultLimit = ' . (int)$top_users . ';

            function renderLeaderboard(selectedLists, limit) {
                // If "__all__" or none selected → use all list IDs
                if (selectedLists.includes("__all__") || selectedLists.length === 0) {
                    selectedLists = allLists.map(l => l.listid.toString());
                }

                let ranked = allUsers.map(u => {
                    let total = 0;
                    let filtered = {};
                    for (const lid in u.lists) {
                        if (selectedLists.includes(lid.toString())) {
                            filtered[lid] = u.lists[lid];
                            total += u.lists[lid];
                        }
                    }
                    return {...u, lists: filtered, total};
                }).filter(u => u.total > 0);

                ranked.sort((a,b) => b.total - a.total || a.handle.localeCompare(b.handle));
                ranked = ranked.slice(0, limit);

                const showTotal = selectedLists.length > 1;

                let html = "<table class=\'leaderboard-table\'><thead><tr><th>Rank</th><th>User</th>";
                selectedLists.forEach(lid => {
                    const list = allLists.find(x => x.listid == lid);
                    html += "<th>"+(list ? list.listname : "List "+lid)+"</th>";
                });
                if (showTotal)
                    html += "<th>Total</th>";
                html += "</tr></thead><tbody>";

                if (ranked.length === 0) {
                    html += "<tr><td colspan=\'"+(selectedLists.length+(showTotal?3:2))+"\' style=\'text-align:center;\'>No results</td></tr>";
                } else {
                    let rank = 0, prevTotal=null, prevRank=0;
                    ranked.forEach(u => {
                        rank++;
                        if (u.total === prevTotal) u.rank = prevRank;
                        else u.rank = rank;
                        prevRank = u.rank; prevTotal = u.total;

                        html += "<tr><td>"+u.rank+"</td>";
                        html += "<td><a href=\'index.php?qa=user/"+u.handle+"\'>"+u.handle+"</a></td>";
                        selectedLists.forEach(lid => {
                            let val = u.lists[lid] ? u.lists[lid] : 0;
                            html += "<td style=\'text-align:center;\'>"+val+"</td>";
                        });
                        if (showTotal)
                            html += "<td style=\'text-align:center; font-weight:bold;\'>"+u.total+"</td>";
                        html += "</tr>";
                    });
                }

                html += "</tbody></table>";
                document.getElementById("leaderboardTable").innerHTML = html;
            }

            // --- Apply Filter ---
            document.getElementById("applyFilter").addEventListener("click", () => {
                const lists = Array.from(document.getElementById("listsSelect").selectedOptions).map(o => o.value);
                const limit = parseInt(document.getElementById("limitInput").value);
                renderLeaderboard(lists, limit);
            });

            // --- Reload on months change ---
            document.getElementById("monthsSelect").addEventListener("change", () => {
                const months = document.getElementById("monthsSelect").value;
                const limit = document.getElementById("limitInput").value;
                const url = new URL(window.location.href);
                url.searchParams.set("months", months);
                url.searchParams.set("limit", limit);
                window.location.href = url.toString();
            });

            // --- Initial render ---
            renderLeaderboard(["__all__"], defaultLimit);
        </script>';

        // --- CSS styling (light + dark, with horizontal scroll) ---
        $html .= '<style>
            #leaderboardContainer { overflow-x: auto; margin-top: 10px; }
            .leaderboard-table { min-width: 900px; width: 100%; border-collapse: collapse; }
            .leaderboard-table th, .leaderboard-table td { border: 1px solid #ddd; padding: 8px; white-space: nowrap; }
            .leaderboard-table th { background: #0073e6; color: #fff; text-align: center; }
            .leaderboard-table tr:nth-child(even) { background: #fafafa; }
            .leaderboard-table tr:hover { background: #f1f7ff; }
            .qa-info-box { background: #f0f7ff; border: 1px solid #bcd; padding: 10px 15px; margin-bottom: 15px; border-radius: 6px; }

            /* --- Dark Mode Styles --- */
            @media (prefers-color-scheme: dark) {
                .leaderboard-table th { background: #333; color: #eee; }
                .leaderboard-table td { border-color: #444; color: #ddd; }
                .leaderboard-table tr:nth-child(even) { background: #2a2a2a; }
                .leaderboard-table tr:hover { background: #333; }
                .qa-info-box { background: #2b2b2b; border-color: #555; color: #ddd; }
            }
        </style>';

        $content["custom"] = $html;
        return $content;
    }
}
