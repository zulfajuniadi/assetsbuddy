(function($){
	$(document).ready(function(){
		function render(template) {
			$('#outlet').html(template({item : 'templates'}));
		}

		if($('#minifier').html() === undefined) {
			$('script[type="text/x-handlebars"]').each(function(){
				$.get(this.src, function(res){
					var template = Handlebars.compile(res);
					render(template);
				});
			})
		} else {
			var template = Handlebars.compile($('#minifier').html());
			render(template);
		}
	});
})(jQuery);