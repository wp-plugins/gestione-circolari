jQuery.noConflict();
(function($) {
	$(function() {		 
		$('#NavPag_Circolari').keypress(function() { //start function when Random button is clicked
			window.location = $('#UrlNavPagCircolari').attr('value')+$('#NavPag_Circolari').attr('value');
		});
	});
})(jQuery);