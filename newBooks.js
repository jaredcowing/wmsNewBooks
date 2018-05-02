$(document).ready(function(){
	
	
	
	window.onload=function(){
	$('.cover').each(function(){
		if($(this).height()>30){
			$(this).parent().addClass('coverUnfaded').find('.details').addClass('whiteBG').hide().parent().find('.title').addClass('whiteBG').hide().parent().css('background-color','transparent');
			$(this).parent().append("<div class='bookMask'></div>");
			$(this).css({'width':'200px','height':'auto','max-height':'250px'});
		}
		else{
		}
	});
	$('.bookMask').hide();
	}
	
	$('body').on('keypress click','#newBooksGo',function(e){				//It might be better to switch the whole application over to fund codes to avoid these raw string URL problems
		if(e.which === 13 || e.type === 'click'){
			var fund=$('#subjectChooser').val();
			var age=$('#dateChooser').val();
		}
		while(fund.indexOf('&')!=-1){
			var whereisit=fund.indexOf('&');
			fund=fund.substr(0,whereisit)+"^^"+fund.substr(whereisit+1);
		}
		while(fund.indexOf('/')!=-1){
			var whereisit=fund.indexOf('/');
			fund=fund.substr(0,whereisit)+"~~"+fund.substr(whereisit+1);
		}
		var urlpass=encodeURI(fund+"/"+age);
		window.location.href="https://jaredcowing.com/newBooks/index.php/Bookview/viewFA/"+urlpass;
		//alert(urlpass);
	});
	
	$('body').on('mouseenter','.coverUnfaded',function(){
		$(this).find('.details').fadeIn('200');
		$(this).find('.title').fadeIn('200');
		$(this).find('.bookMask').fadeIn('200');
	});
	$('body').on('mouseleave','.coverUnfaded',function(){
		$(this).find('.details').fadeOut('200');
		$(this).find('.title').fadeOut('200');
		$(this).find('.bookMask').fadeOut('200');
	});
});