jQuery(document).ready(function($) {
    $('body').on('click', '.yfs-follow-button', function(e) {
        e.preventDefault();
        var $button = $(this);

        if (!yfs_ajax_obj.logged_in) {
            window.location.href = yfs_ajax_obj.login_url;
            return;
        }

        if ($button.hasClass('is-loading')) {
            return; // Prevent multiple clicks
        }

        var term_taxonomy_id = $button.data('term-taxonomy-id');
        var term_name = $button.data('term-name') || ''; // Get term name from data attribute

        $button.addClass('is-loading').prop('disabled', true);
        var $iconUse = $button.find('svg use');
        var originalIconHref = $iconUse.attr('xlink:href');
        var originalButtonTextSpan = $button.find('.yfs-button-text');
        var originalButtonText = originalButtonTextSpan.length ? originalButtonTextSpan.text() : '';


        // Use a spinner icon if defined, otherwise just disable
        if (yfs_ajax_obj.icon_spinner) {
            $iconUse.attr('xlink:href', '#' + yfs_ajax_obj.icon_spinner);
        }
        if (originalButtonTextSpan.length) {
            originalButtonTextSpan.text(yfs_ajax_obj.i18n.loading);
        }


        $.ajax({
            url: yfs_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'yfs_toggle_follow',
                nonce: yfs_ajax_obj.nonce,
                term_taxonomy_id: term_taxonomy_id,
            },
            success: function(response) {
                if (response.success) {
                    var isNowFollowing = (response.data.status === 'followed');
                    $button.data('subscribed', isNowFollowing); // Update data attribute
                    $button.toggleClass('is-following', isNowFollowing);
                    $button.attr('aria-pressed', isNowFollowing ? 'true' : 'false');
                    
                    var newIcon = isNowFollowing ? yfs_ajax_obj.icon_following : yfs_ajax_obj.icon_follow;
                    $iconUse.attr('xlink:href', '#' + newIcon);

                    var newButtonText = isNowFollowing ? yfs_ajax_obj.i18n.following : yfs_ajax_obj.i18n.follow;
                    var newTitle = (isNowFollowing ? yfs_ajax_obj.i18n.unfollow : yfs_ajax_obj.i18n.follow) + (term_name ? ' ' + term_name : '');
                    
                    if (originalButtonTextSpan.length) {
                         originalButtonTextSpan.text(newButtonText + (term_name && $button.hasClass('page-level-subscribe-button') ? ' ' + term_name : ''));
                    }
                    $button.attr('title', newTitle);

                    // Optional: Show a success message (e.g., using a toast notification library)
                    // console.log(response.data.message);

                } else {
                    alert(response.data.message || yfs_ajax_obj.i18n.error);
                    $iconUse.attr('xlink:href', originalIconHref); // Revert icon on error
                    if (originalButtonTextSpan.length) {
                         originalButtonTextSpan.text(originalButtonText);
                    }
                }
            },
            error: function() {
                alert(yfs_ajax_obj.i18n.error);
                $iconUse.attr('xlink:href', originalIconHref); // Revert icon on error
                 if (originalButtonTextSpan.length) {
                    originalButtonTextSpan.text(originalButtonText);
                }
            },
            complete: function() {
                $button.removeClass('is-loading').prop('disabled', false);
            }
        });
    });
});