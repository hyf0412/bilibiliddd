function unableScroll() {
	var top = $(document).scrollTop();
	$(document).on('scroll.unable',function (e) {
		$(document).scrollTop(top);
	})
}
function enableScroll() {
	$(document).unbind("scroll.unable");
}
$(function(){
	var hmpic = $('#hmpic');
	var nav = $('.nav li');
	$('.mobile a').click(function(){
		$(this).siblings('.qr').stop().fadeToggle('fast');
	});
	$('.hmnews .tab a').click(function(e){
		var _index = $(this).parent().index();
		$(this).addClass('cur').parent().siblings('.dot').children('a').removeClass('cur');
		$('.hmnews ul').eq(_index).stop().fadeIn('fast').siblings().hide();
	});

	$('.navbtn').click(function(){
	    $('.search').hide();
		if($(this).hasClass('open')){
			enableScroll();
			$(this).removeClass('open');
			$('.nav').removeClass('open');
		}else{
			unableScroll();
			$(this).addClass('open');
			$('.nav').addClass('open');
		}
	});
	
	nav.each(function(){
		var _href = $(this).children('a').attr('href');
		if(_href == _url){
			if($(this).parents('li').length > 0){
				$(this).parents('li').addClass('cur');
			}else{
				$(this).addClass('cur');
			}			
		}
		if($(this).find('li').length > 0){
			$(this).children('a').after('<em></em>');
		}
	});
	nav.children('em').click(function(){
		$(this).toggleClass('open').siblings('ul').stop().slideToggle('fast').parent().siblings('li').children('em').removeClass('open').siblings('ul').stop().slideUp('fast');
	});

	var down = $('.downfile');
	$('.appdown a').click(function(){
		down.stop().fadeToggle('fast');
	});
	down.children('.close').click(function(){
		$(this).parent().stop().fadeOut('fast');
	});
	/*æœç´¢*/	
	$('.searchbtn').click(function(){
	    enableScroll();
		$('.search').stop().fadeToggle('fast');
		$('.nav, .navbtn').removeClass('open');
	});	
	$('.backtop').click(function(){
		$('body,html').animate({scrollTop:0},300);
	});	
	
	if($('#screen').length > 0){
		$('#screen').on('initialize.owl.carousel initialized.owl.carousel',	function(e) {
			$(this).show();                 
		});
		$('#screen').owlCarousel({
			items:3,			
			loop:true, 
			mouseDrag:false,
			autoplay:true,
			nav:true,	
			dots:false
		}); 
	}
	$('.appscreen .pic a').click(function(event){
		event.preventDefault();
		var _pic = '<div class="popupImg"><div class="box">'+$(this).html()+'<span></span></div></div>';
		$('body').append(_pic);
		$('.popupImg span').click(function(){		
			$('.popupImg').remove();
		});
		$(document).click(function(event){
			if($(event.target).closest('img').length === 0){
				$('.popupImg').remove();
			}
		});
	});
	
	$('.hotapps .top h3').click(function(){
		$(this).addClass('cur').siblings().removeClass('cur');
		var _index = $(this).index();
		$(this).parent().siblings().children('ul').eq(_index).stop().fadeIn('fast').siblings().hide();
	});
	$(window).resize(function(){
		if($('body').width() > 960){			
			nav.on('mouseover mouseleave');
			nav.mouseover(function(){
				$(this).addClass('on').children('ul').stop().slideDown('fast');
			}).mouseleave(function(){
				$(this).removeClass('on').children('ul').stop().slideUp('fast');
			});
		}else{
		    nav.off('mouseover mouseleave');
		}
		if($('body').width() > 768){
			if(hmpic.length > 0 && hmpic.hasClass('owl-carousel')){
				hmpic.trigger('destroy.owl.carousel').removeClass('owl-carousel');
			}	
		}else{
			if(hmpic.length > 0 && !hmpic.hasClass('owl-carousel')){
				hmpic.addClass('owl-carousel').owlCarousel({
					items:1,			
					loop:true, 
					mouseDrag:false,
					autoplay:true,
					nav:false,	
					dots:true
				}); 
			}	
		}
	}).trigger('resize');
	
});