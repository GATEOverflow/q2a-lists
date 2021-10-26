$(document).ready(function()
{
	// prevent submit
	$("#qa-userlists").attr("type", "button");
	
	$("#qa-userlists").click( function()
	{
		var postid = $(this).data("postid");
		
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
var listids = Array();;
//			var listid = $("input[name=qa-spam-reason-radio]:checked").val();
		$('input:checkbox[name=qa-lists-check]:checked').each(function(){
			listids.push($(this).val());
	});
	
			var dataArray = {
				questionid: listsQuestionid,
				list: listids,
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
						// if success, reload page
						location.reload();
					}
					else
					{
						alert(data);
					}
				 },
				 error: function(data)
				 {
					console.log("Ajax error:");
					console.log(data);
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
