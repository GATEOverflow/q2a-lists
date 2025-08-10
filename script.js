$(document).ready(function()
{
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
		
		$(".qa-lists-wrap .closer").click( function()
		{
			$("#qa-lists-popup").hide();
		});
		
		// focus on first element, then Enter and Escape key work
		$('.qa-lists-wrap input').first().focus();
		
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
				 data: { ajaxdata: senddata },
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
						location.reload();
					}
					else
					{
						alert(data);
						
					}
					// Check if list 0 was added (favorite)
					if (addList.includes("0")) {
						document.querySelector(`button[name="favorite_Q_${listsQuestionid}_1"]`)?.click();
					}

					// Check if list 0 was removed (unfavorite)
					if (removeList.includes("0")) {
						document.querySelector(`button[name="favorite_Q_${listsQuestionid}_0"]`)?.click();
					}
				 },
				 error: function(data)
				 {
					console.log("Ajax error:",data);
				 }
			}).always(function() {
					// Run these no matter what ajax response
					if (addList.includes("0")) {
						document.querySelector(`button[name="favorite_Q_${listsQuestionid}_1"]`)?.click();
					}
					if (removeList.includes("0")) {
						document.querySelector(`button[name="favorite_Q_${listsQuestionid}_0"]`)?.click();
					}
				});
		});
		
	}); // END click
	
	
	// mouse click on flagbox closes div
	$('#lists-popup').click(function(e)
	{
		if(e.target == this)
		{ 
			$(this).find('.closer').click();
		}
	});
	
});
