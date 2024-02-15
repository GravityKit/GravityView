/**
 * Responsible for copying the short codes from the list and edit page.
 * @since $ver$
 */
(function ($) {
	$(document).on('ready', function () {
		new ClipboardJS('.gv-shortcode input.code', {
			text: function (trigger) {
				return $(trigger).val();
			}
		});

		$('.gv-shortcode input.code').on('click', function (e) {
			e.preventDefault();
			var $el = $(this).closest('.gv-shortcode').find('.copied');
			$el.show();
			setTimeout(function () {
				$el.fadeOut();
			}, 1000);
		});
	});
})(jQuery);
