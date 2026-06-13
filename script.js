$(document).ready(function()
{
	var i=0;
	var favClickedIntent = null; // tracks whether the last favorite click was "add" or "remove"
	 // Store the old list IDs before the user changes anything
    let oldListIds = [];
	
	// prevent submit
	$("#qa-userlists").attr("type", "button");
	
	$("#qa-userlists").click( function()
	{
		var postid = $(this).data("postid");
		
		// Capture current checked lists as the "old" state
        oldListIds = $('input:checkbox[name=qa-lists-check]:checked')
            .map(function() { return $(this).val(); })
            .get();
		
		// remove button so no double inserts
		// $(this).remove();
		
		$("#qa-lists-popup").show();
		
		// focus on first element, then Enter and Escape key work
		$('.qa-lists-wrap input').first().focus();
	});
	$(".qa-lists-wrap .closer").click( function()
	{
		$("#qa-lists-popup").hide();
	});
	$(".qa-go-list-send-button").click( function()
	{
		let newListIds = $('input:checkbox[name=qa-lists-check]:checked').map(function() { return $(this).val(); }).get();

		// Compare arrays
		let addList = newListIds.filter(id => !oldListIds.includes(id));
		let removeList = oldListIds.filter(id => !newListIds.includes(id));
		
		var dataArray = {
			questionid: listsQuestionid,
			addList: addList,
			removeList: removeList,
		};
		
		var senddata = JSON.stringify(dataArray);
		
		// send ajax
		$.ajax({
				type: "POST",
				url: listsAjaxURL,
				data: { ajaxdata: senddata, code: listsCsrfCode },
				dataType:"json",
				cache: false,
				success: function(data)
				{	
				if(typeof data.error !== "undefined")
				{
					alert(data.error);
				}
				else if(typeof data.success !== "undefined")
				{
					//location.reload();
					$("#qa-lists-popup").hide();
				}
				else
				{
					alert(data);
					
				}
				},
				error: function(data)
				{
				console.log("Ajax error:",data);
				}
		}).always(function() {
				// Run these no matter what ajax response
				if (addList.includes("0")) {
					//document.querySelector(`button[name="favorite_Q_${listsQuestionid}_1"]`)?.click();
					document.querySelector(`[name="favorite_Q_${listsQuestionid}_1"]`)?.click();
				}
				if (removeList.includes("0")) {
					//document.querySelector(`button[name="favorite_Q_${listsQuestionid}_0"]`)?.click();
					document.querySelector(`[name="favorite_Q_${listsQuestionid}_0"]`)?.click();
				}
			});
	});
	
	
	// mouse click on flagbox closes div
	$('#lists-popup').click(function(e)
	{
		if(e.target == this)
		{ 
			$(this).find('.closer').click();
		}
	});

		
	
 
 $(document).on("click", `[name^="favorite_Q_${listsQuestionid}_"]`, function() {
	favClickedIntent = $(this).attr("name").endsWith("_1") ? "add" : "remove";
});

$(document).ajaxComplete(function(event, xhr, settings) {
	if (favClickedIntent === null) return;
	if (!settings.data || settings.data.indexOf("qa_operation=favorite") === -1) return;
	
	var cb = document.getElementById("qa-lists-check-0");
	if (cb) cb.checked = (favClickedIntent === "add");
	favClickedIntent = null;
});
	
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

	
});
