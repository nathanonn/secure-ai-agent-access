jQuery(document).ready(function($) {
    // Generate magic link
    $('#saia-generate-link-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $button = $form.find('button[type="submit"]');
        var $result = $('#saia-link-result');
        var userId = $('#saia-user-select').val();
        
        if (!userId) {
            alert('Please select a user');
            return;
        }
        
        $button.prop('disabled', true).text('Generating...');
        
        $.ajax({
            url: saia_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'saia_generate_link',
                nonce: saia_ajax.nonce,
                user_id: userId
            },
            success: function(response) {
                if (response.success) {
                    var linkHtml = '<div class="saia-notice success">' +
                        '<p>Magic link generated successfully!</p>' +
                        '<div class="saia-link-masked" id="saia-masked-link">' + response.data.masked_link + 
                        ' <button type="button" class="button button-small" id="saia-reveal-link">Reveal</button></div>' +
                        '<input type="hidden" id="saia-full-link" value="' + response.data.link + '">' +
                        '<button type="button" class="button button-primary" id="saia-copy-link">Copy Link</button>' +
                        '<p><small>This link will expire after first use or in 24 hours</small></p>' +
                        '</div>';
                    $result.html(linkHtml);
                } else {
                    $result.html('<div class="saia-notice error"><p>' + response.data.message + '</p></div>');
                }
            },
            error: function() {
                $result.html('<div class="saia-notice error"><p>An error occurred. Please try again.</p></div>');
            },
            complete: function() {
                $button.prop('disabled', false).text('Generate Magic Link');
            }
        });
    });
    
    // Reveal/hide link
    $(document).on('click', '#saia-reveal-link', function() {
        var $container = $('#saia-masked-link');
        var fullLink = $('#saia-full-link').val();
        
        $container.removeClass('saia-link-masked').addClass('saia-link-revealed').html(
            fullLink + ' <button type="button" class="button button-small" id="saia-hide-link">Hide</button>'
        );
    });
    
    $(document).on('click', '#saia-hide-link', function() {
        var $container = $('.saia-link-revealed');
        var maskedLink = $('#saia-masked-link').data('masked');
        
        location.reload(); // Simple reload to restore masked state
    });
    
    // Copy link
    $(document).on('click', '#saia-copy-link', function() {
        var $button = $(this);
        var link = $('#saia-full-link').val();
        var originalText = $button.text();
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(link).then(function() {
                // Change button text to show success
                $button.text('Copied!').addClass('saia-copied');
                
                // Revert button text after 2 seconds
                setTimeout(function() {
                    $button.text(originalText).removeClass('saia-copied');
                }, 2000);
            });
        } else {
            // Fallback for older browsers
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(link).select();
            document.execCommand('copy');
            $temp.remove();
            
            // Change button text to show success
            $button.text('Copied!').addClass('saia-copied');
            
            // Revert button text after 2 seconds
            setTimeout(function() {
                $button.text(originalText).removeClass('saia-copied');
            }, 2000);
        }
    });
    
    // Terminate session
    $('.saia-terminate-session').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm(saia_ajax.confirm_terminate)) {
            return;
        }
        
        var $button = $(this);
        var sessionId = $button.data('session-id');
        
        $button.prop('disabled', true).text('Terminating...');
        
        $.ajax({
            url: saia_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'saia_terminate_session',
                nonce: saia_ajax.nonce,
                session_id: sessionId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            },
            complete: function() {
                $button.prop('disabled', false).text('Terminate Session');
            }
        });
    });
    
    // Kill all sessions
    window.saiaKillAllSessions = function() {
        if (!confirm(saia_ajax.confirm_kill_all)) {
            return;
        }
        
        $.ajax({
            url: saia_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'saia_kill_all_sessions',
                nonce: saia_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    };
    
    $('#saia-kill-all-sessions').on('click', function(e) {
        e.preventDefault();
        saiaKillAllSessions();
    });
    
    // Settings sliders
    $('.saia-slider').each(function() {
        var $slider = $(this);
        var $display = $slider.siblings('.saia-slider-display');
        var $input = $slider.siblings('input[type="hidden"]');
        var unit = $slider.data('unit') || '';
        
        $slider.on('input', function() {
            var value = $(this).val();
            $input.val(value);
            
            // Convert seconds to human readable
            if (unit === 'seconds') {
                if (value < 3600) {
                    $display.text(Math.floor(value / 60) + ' minutes');
                } else if (value < 86400) {
                    $display.text(Math.floor(value / 3600) + ' hours');
                } else {
                    $display.text(Math.floor(value / 86400) + ' days');
                }
            } else {
                $display.text(value + ' ' + unit);
            }
        }).trigger('input'); // Trigger to display initial value
    });
    
    // Toggle dependent settings
    $('input[type="checkbox"][data-toggle]').on('change', function() {
        var target = $(this).data('toggle');
        var $target = $('.' + target);
        
        if ($(this).is(':checked')) {
            $target.show();
        } else {
            $target.hide();
        }
    }).trigger('change');
    
    // Manual cleanup
    $('#saia-cleanup-now').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to clean up old data now?')) {
            return;
        }
        
        var $button = $(this);
        $button.prop('disabled', true).text('Cleaning...');
        
        $.ajax({
            url: saia_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'saia_manual_cleanup',
                nonce: saia_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Cleanup completed successfully!');
                    location.reload();
                } else {
                    alert('Cleanup failed. Please try again.');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            },
            complete: function() {
                $button.prop('disabled', false).text('Clean Old Data Now');
            }
        });
    });
    
    // Link filtering
    $('#saia-link-filter').on('change', function() {
        var filter = $(this).val();
        
        if (filter === 'all') {
            $('.saia-link-row').show();
        } else {
            $('.saia-link-row').hide();
            $('.saia-link-row[data-status="' + filter + '"]').show();
        }
    });
    
    // Link search
    $('#saia-link-search').on('keyup', function() {
        var search = $(this).val().toLowerCase();
        
        $('.saia-link-row').each(function() {
            var $row = $(this);
            var text = $row.text().toLowerCase();
            
            if (text.indexOf(search) > -1) {
                $row.show();
            } else {
                $row.hide();
            }
        });
    });
    
    // Auto-refresh active sessions
    if ($('.saia-active-sessions').length) {
        setInterval(function() {
            $.ajax({
                url: saia_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'saia_get_active_sessions',
                    nonce: saia_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('.saia-active-sessions').html(response.data.html);
                    }
                }
            });
        }, 30000); // Refresh every 30 seconds
    }
    
    // Revoke link
    $('.saia-revoke-link').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to revoke this link?')) {
            return;
        }
        
        var $button = $(this);
        var linkId = $button.data('link-id');
        
        $button.prop('disabled', true).text('Revoking...');
        
        $.ajax({
            url: saia_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'saia_revoke_link',
                nonce: saia_ajax.nonce,
                link_id: linkId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            },
            complete: function() {
                $button.prop('disabled', false).text('Revoke');
            }
        });
    });

    // Delete link
    $('.saia-delete-link').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to delete this link?')) {
            return;
        }
        
        var $button = $(this);
        var linkId = $button.data('link-id');
        
        $button.prop('disabled', true).text('Deleting...');
        
        $.ajax({
            url: saia_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'saia_delete_link',
                nonce: saia_ajax.nonce,
                link_id: linkId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            },
            complete: function() {
                $button.prop('disabled', false).text('Delete');
            }
        });
    });

    // Bulk actions
    $('.bulkactions .action').on('click', function(e) {
        e.preventDefault();
        
        var action = $('select[name="bulk_action"]').val();
        if (!action) {
            alert('Please select a bulk action');
            return;
        }
        
        var linkIds = [];
        $('input[name="link_ids[]"]:checked').each(function() {
            linkIds.push($(this).val());
        });
        
        if (linkIds.length === 0) {
            alert('Please select at least one link');
            return;
        }
        
        var confirmMsg = action === 'delete' ? 
            'Are you sure you want to delete the selected links?' : 
            'Are you sure you want to revoke the selected links?';
            
        if (!confirm(confirmMsg)) {
            return;
        }
        
        $.ajax({
            url: saia_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'saia_bulk_link_action',
                nonce: saia_ajax.nonce,
                bulk_action: action,
                link_ids: linkIds
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    });
});