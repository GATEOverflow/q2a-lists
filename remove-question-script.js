$(document).ready(function()
{
		
	$(document).on("click", ".qa-list-remove-btn", function(e) {

		e.preventDefault();

		const btn = $(this);
		const questionid = btn.data("questionid");
		const listid = btn.data("listid");

		if (!confirm("Remove this question from the list?")) {
			return;
		}

		$.ajax({
			type: "POST",
			url: listsAjaxURL,
			data: {
				ajaxdata: JSON.stringify({
					questionid: questionid,
					addList: [],
					removeList: [String(listid)]
				})
			},
			dataType: "json",
			success: function(response) {

				if (response.success) {

					// Smooth remove
					btn.closest(".qa-q-list-item").fadeOut(300, function() {
						$(this).remove();
					});

				} else if (response.error) {
					alert(response.error);
				}
			},
			error: function(err) {
				console.log("Ajax error:", err);
			}
		});

	});
	$(document).on("click", ".qa-list-remove-all-btn", function(e) {
		e.preventDefault();
	
		const btn = $(this);
		const questionid = btn.data("questionid");
	
		if (!confirm("Remove this question from ALL lists?")) {
			return;
		}
	
		$.ajax({
			type: "POST",
			url: listsAjaxURL,
			data: {
				ajaxdata: JSON.stringify({
					questionid: questionid,
					addList: [],
					removeList: [],
					removeAll: true
				})
			},
			dataType: "json",
			success: function(response) {
				if (response.success) {
					// Remove the question row from UI
					btn.closest(".qa-q-list-item").fadeOut(300, function() {
						$(this).remove();
					});
					// Unfavorite since list 0 (favorites) is being removed
					document.querySelector(`[name="favorite_Q_${questionid}_0"]`)?.click();
				} else if (response.error) {
					alert(response.error);
				}
			},
			error: function(err) {
				console.log("Ajax error:", err);
			}
		});
	});
});
