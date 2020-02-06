$(document).ready(function(){
	var baseURL="https://jaredcowing.com/newBooks";	//Put your base URL here, without end slash
	window.onload=function(){
	
	$('.cover').each(function(){
		if($(this).height()>30){
			$(this).parent().addClass('coverUnfaded').find('.details').addClass('whiteBG').hide().parent().find('.title').addClass('whiteBG').hide().parent().css('background-color','transparent');
			$(this).parent().append("<div class='bookMask'></div>");
			$(this).css({'width':'200px','height':'auto','max-height':'250px'});
		}
		else{
			$(this).remove();
		}
	});
	$('.bookMask').hide();
	}
	$('#loadingCover').remove();
	
	$('body').on('keypress click','#newBooksGo',function(e){				//Just in case fund code has URL characters
		
		if(e.which === 13 || e.type === 'click'){
			var fund=$('#subjectChooser').val();
			var age=$('#dateChooser').val();
			$('#newBooksGo img').attr('src',baseURL+'/images/spinning-wheel.gif');
			$('#newBooksGo img').css('padding-top','15px');
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
		window.location.href=baseURL+"/index.php/Bookview/viewFA/"+urlpass;
		//alert(urlpass);
	});
	
	$('body').on('keypress click','#newBooksGoM',function(e){				//Just in case fund code has URL characters
		if(e.which === 13 || e.type === 'click'){
			var fund=$('#subjectChooser').val();
			var age=$('#dateChooser').val();
			$('#newBooksGo img').attr('src',baseURL+'/images/spinning-wheel.gif');
			$('#newBooksGo img').css('padding-top','15px');
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
		window.location.href=baseURL+"/index.php/Bookview/viewFAS/"+urlpass+"/m";
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