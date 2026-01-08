/**
 * Redirectr Admin JavaScript
 *
 * @package Redirectr
 */

(function($) {
	'use strict';

	var Redirectr = {
		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			// Delete redirect
			$(document).on('click', '.redirectr-delete', this.deleteRedirect);

			// Toggle redirect status
			$(document).on('click', '.redirectr-toggle-status', this.toggleStatus);

			// Delete 404 log
			$(document).on('click', '.redirectr-delete-404', this.delete404);

			// Ignore 404
			$(document).on('click', '.redirectr-ignore-404', this.ignore404);

			// Convert 404 to redirect - show modal
			$(document).on('click', '.redirectr-convert-404', this.showConvertModal);

			// Convert 404 - submit
			$(document).on('click', '.redirectr-convert-submit', this.convertToRedirect);

			// Modal close
			$(document).on('click', '.redirectr-modal-close', this.closeModal);
			$(document).on('click', '.redirectr-modal', function(e) {
				if ($(e.target).hasClass('redirectr-modal')) {
					Redirectr.closeModal();
				}
			});

			// Close modal on escape key
			$(document).on('keydown', function(e) {
				if (e.key === 'Escape') {
					Redirectr.closeModal();
				}
			});
		},

		deleteRedirect: function(e) {
			e.preventDefault();

			if (!confirm(redirectr_admin.strings.confirm_delete)) {
				return;
			}

			var $button = $(this);
			var id = $button.data('id');
			var $row = $button.closest('tr');

			$row.addClass('redirectr-loading');

			$.ajax({
				url: redirectr_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'redirectr_delete_redirect',
					security: redirectr_admin.nonce,
					id: id
				},
				success: function(response) {
					if (response.success) {
						$row.fadeOut(300, function() {
							$(this).remove();
							Redirectr.updateEmptyState();
						});
					} else {
						alert(response.data.message);
						$row.removeClass('redirectr-loading');
					}
				},
				error: function() {
					alert('An error occurred. Please try again.');
					$row.removeClass('redirectr-loading');
				}
			});
		},

		toggleStatus: function(e) {
			e.preventDefault();

			var $button = $(this);
			var id = $button.data('id');
			var $row = $button.closest('tr');

			$row.addClass('redirectr-loading');

			$.ajax({
				url: redirectr_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'redirectr_toggle_status',
					security: redirectr_admin.nonce,
					id: id
				},
				success: function(response) {
					if (response.success) {
						var $badge = $row.find('.redirectr-status');
						var newStatus = response.data.new_status;

						$badge
							.removeClass('redirectr-status-active redirectr-status-inactive')
							.addClass('redirectr-status-' + newStatus)
							.text(redirectr_admin.strings[newStatus]);

						// Update the toggle link text
						$button.text(newStatus === 'active' ? 'Deactivate' : 'Activate');
					} else {
						alert(response.data.message);
					}
					$row.removeClass('redirectr-loading');
				},
				error: function() {
					alert('An error occurred. Please try again.');
					$row.removeClass('redirectr-loading');
				}
			});
		},

		delete404: function(e) {
			e.preventDefault();

			if (!confirm(redirectr_admin.strings.confirm_delete)) {
				return;
			}

			var $button = $(this);
			var id = $button.data('id');
			var $row = $button.closest('tr');

			$row.addClass('redirectr-loading');

			$.ajax({
				url: redirectr_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'redirectr_delete_404',
					security: redirectr_admin.nonce,
					id: id
				},
				success: function(response) {
					if (response.success) {
						$row.fadeOut(300, function() {
							$(this).remove();
							Redirectr.updateEmptyState();
						});
					} else {
						alert(response.data.message);
						$row.removeClass('redirectr-loading');
					}
				},
				error: function() {
					alert('An error occurred. Please try again.');
					$row.removeClass('redirectr-loading');
				}
			});
		},

		ignore404: function(e) {
			e.preventDefault();

			var $button = $(this);
			var id = $button.data('id');
			var $row = $button.closest('tr');

			$row.addClass('redirectr-loading');

			$.ajax({
				url: redirectr_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'redirectr_ignore_404',
					security: redirectr_admin.nonce,
					id: id
				},
				success: function(response) {
					if (response.success) {
						// Update status badge
						var $badge = $row.find('.redirectr-status');
						$badge
							.removeClass('redirectr-404-status-new')
							.addClass('redirectr-404-status-ignored')
							.text(redirectr_admin.strings.ignored);

						// Remove the ignore action link
						$button.closest('.ignore').remove();
					} else {
						alert(response.data.message);
					}
					$row.removeClass('redirectr-loading');
				},
				error: function() {
					alert('An error occurred. Please try again.');
					$row.removeClass('redirectr-loading');
				}
			});
		},

		showConvertModal: function(e) {
			e.preventDefault();

			var id = $(this).data('id');
			var url = $(this).data('url');

			$('#redirectr-convert-id').val(id);
			$('#redirectr-convert-source').text(url);
			$('#redirectr-convert-destination').val('').focus();
			$('#redirectr-convert-modal').show();
		},

		convertToRedirect: function(e) {
			e.preventDefault();

			var $modal = $('#redirectr-convert-modal');
			var id = $('#redirectr-convert-id').val();
			var destination = $('#redirectr-convert-destination').val();

			if (!destination) {
				alert(redirectr_admin.strings.enter_destination);
				$('#redirectr-convert-destination').focus();
				return;
			}

			var $button = $(this);
			$button.prop('disabled', true).text('Creating...');

			$.ajax({
				url: redirectr_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'redirectr_convert_404',
					security: redirectr_admin.nonce,
					id: id,
					destination: destination
				},
				success: function(response) {
					if (response.success) {
						$modal.hide();

						// Update the row
						var $row = $('[data-id="' + id + '"]').closest('tr');
						if ($row.length === 0) {
							$row = $('input[value="' + id + '"]').closest('tr');
						}

						// Update status badge
						var $badge = $row.find('.redirectr-status');
						$badge
							.removeClass('redirectr-404-status-new redirectr-404-status-ignored')
							.addClass('redirectr-404-status-redirected')
							.text(redirectr_admin.strings.redirected);

						// Remove convert and ignore action links
						$row.find('.convert, .ignore').remove();

						// Show success message (optional - could use a toast instead)
						// alert(response.data.message);
					} else {
						alert(response.data.message);
					}
				},
				error: function() {
					alert('An error occurred. Please try again.');
				},
				complete: function() {
					$button.prop('disabled', false).text('Create Redirect');
				}
			});
		},

		closeModal: function() {
			$('.redirectr-modal').hide();
		},

		updateEmptyState: function() {
			var $table = $('.wp-list-table');
			if ($table.find('tbody tr').length === 0) {
				$table.find('tbody').append(
					'<tr class="no-items"><td class="colspanchange" colspan="' + $table.find('thead th').length + '">No items found.</td></tr>'
				);
			}
		}
	};

	$(document).ready(function() {
		Redirectr.init();
	});

})(jQuery);
