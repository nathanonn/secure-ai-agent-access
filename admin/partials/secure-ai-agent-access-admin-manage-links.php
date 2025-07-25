<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

global $wpdb;
$links_table = $wpdb->prefix . 'saia_magic_links';

$filter = isset( $_GET['filter'] ) ? sanitize_text_field( $_GET['filter'] ) : 'all';
$search = isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';

$where_clause = '';
if ( $filter !== 'all' ) {
    $where_clause = $wpdb->prepare( " WHERE l.status = %s", $filter );
}

if ( ! empty( $search ) ) {
    $search_like = '%' . $wpdb->esc_like( $search ) . '%';
    if ( empty( $where_clause ) ) {
        $where_clause = $wpdb->prepare( " WHERE (l.token LIKE %s OR u.user_login LIKE %s)", $search_like, $search_like );
    } else {
        $where_clause .= $wpdb->prepare( " AND (l.token LIKE %s OR u.user_login LIKE %s)", $search_like, $search_like );
    }
}

$links = $wpdb->get_results(
    "SELECT l.*, u.user_login, u.display_name 
     FROM $links_table l 
     LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID 
     $where_clause
     ORDER BY l.created_at DESC"
);
?>

<div class="wrap">
    <h1><?php esc_html_e( 'Manage Links', 'secure-ai-agent-access' ); ?></h1>
    
    <div class="tablenav top">
        <div class="alignleft actions">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=saia-generate-link' ) ); ?>" class="button button-primary">
                <?php esc_html_e( '+ Generate New Link', 'secure-ai-agent-access' ); ?>
            </a>
        </div>
        
        <div class="alignright">
            <form method="get" style="display: inline-block;">
                <input type="hidden" name="page" value="saia-manage-links">
                <label for="saia-link-filter"><?php esc_html_e( 'Filter:', 'secure-ai-agent-access' ); ?></label>
                <select name="filter" id="saia-link-filter">
                    <option value="all" <?php selected( $filter, 'all' ); ?>><?php esc_html_e( 'All', 'secure-ai-agent-access' ); ?></option>
                    <option value="unused" <?php selected( $filter, 'unused' ); ?>><?php esc_html_e( 'Unused', 'secure-ai-agent-access' ); ?></option>
                    <option value="used" <?php selected( $filter, 'used' ); ?>><?php esc_html_e( 'Used', 'secure-ai-agent-access' ); ?></option>
                    <option value="expired" <?php selected( $filter, 'expired' ); ?>><?php esc_html_e( 'Expired', 'secure-ai-agent-access' ); ?></option>
                </select>
                <input type="submit" class="button" value="<?php esc_attr_e( 'Apply', 'secure-ai-agent-access' ); ?>">
            </form>
            
            <form method="get" style="display: inline-block; margin-left: 10px;">
                <input type="hidden" name="page" value="saia-manage-links">
                <input type="text" name="search" id="saia-link-search" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search...', 'secure-ai-agent-access' ); ?>">
                <input type="submit" class="button" value="<?php esc_attr_e( 'Search', 'secure-ai-agent-access' ); ?>">
            </form>
        </div>
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 30px;"></th>
                <th><?php esc_html_e( 'Status', 'secure-ai-agent-access' ); ?></th>
                <th><?php esc_html_e( 'Link', 'secure-ai-agent-access' ); ?></th>
                <th><?php esc_html_e( 'User', 'secure-ai-agent-access' ); ?></th>
                <th><?php esc_html_e( 'Created', 'secure-ai-agent-access' ); ?></th>
                <th><?php esc_html_e( 'Expires/Used', 'secure-ai-agent-access' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'secure-ai-agent-access' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( ! empty( $links ) ) : ?>
                <?php foreach ( $links as $link ) : 
                    $masked_url = substr( site_url(), 0, -10 ) . '...' . substr( $link->token, -6 );
                    $full_url = add_query_arg( 'ai_token', $link->token, wp_login_url() );
                    $status_icon = '';
                    $status_text = '';
                    $expires_text = '-';
                    
                    switch ( $link->status ) {
                        case 'used':
                            $status_icon = '<span class="dashicons dashicons-yes-alt" style="color: #72aee6;"></span>';
                            $status_text = __( 'Used', 'secure-ai-agent-access' );
                            $expires_text = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $link->used_at ) );
                            break;
                        case 'unused':
                            $status_icon = '<span class="dashicons dashicons-clock" style="color: #00a32a;"></span>';
                            $status_text = __( 'Unused', 'secure-ai-agent-access' );
                            $expires_text = human_time_diff( time(), strtotime( $link->expires_at ) );
                            break;
                        case 'expired':
                            $status_icon = '<span class="dashicons dashicons-dismiss" style="color: #d63638;"></span>';
                            $status_text = __( 'Expired', 'secure-ai-agent-access' );
                            $expires_text = '-';
                            break;
                    }
                ?>
                    <tr class="saia-link-row" data-status="<?php echo esc_attr( $link->status ); ?>">
                        <td>
                            <input type="checkbox" name="link_ids[]" value="<?php echo esc_attr( $link->id ); ?>">
                        </td>
                        <td>
                            <?php echo $status_icon; ?> <?php echo esc_html( $status_text ); ?>
                        </td>
                        <td>
                            <span class="saia-link-masked"><?php echo esc_html( $masked_url ); ?></span>
                            <span class="row-actions">
                                <a href="#" class="saia-view-full-link" data-link="<?php echo esc_attr( $full_url ); ?>">
                                    <?php esc_html_e( 'View Full', 'secure-ai-agent-access' ); ?>
                                </a> |
                                <a href="#" class="saia-copy-link" data-link="<?php echo esc_attr( $full_url ); ?>">
                                    <?php esc_html_e( 'Copy', 'secure-ai-agent-access' ); ?>
                                </a>
                            </span>
                        </td>
                        <td><?php echo esc_html( $link->display_name ); ?></td>
                        <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $link->created_at ) ) ); ?></td>
                        <td><?php echo esc_html( $expires_text ); ?></td>
                        <td>
                            <?php if ( $link->status === 'unused' ) : ?>
                                <a href="#" class="saia-revoke-link button button-small" data-link-id="<?php echo esc_attr( $link->id ); ?>">
                                    <?php esc_html_e( 'Revoke', 'secure-ai-agent-access' ); ?>
                                </a>
                            <?php else : ?>
                                <a href="#" class="saia-delete-link button button-small" data-link-id="<?php echo esc_attr( $link->id ); ?>">
                                    <?php esc_html_e( 'Delete', 'secure-ai-agent-access' ); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="7"><?php esc_html_e( 'No links found.', 'secure-ai-agent-access' ); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="tablenav bottom">
        <div class="alignleft actions bulkactions">
            <select name="bulk_action">
                <option value=""><?php esc_html_e( 'Bulk Actions', 'secure-ai-agent-access' ); ?></option>
                <option value="delete"><?php esc_html_e( 'Delete', 'secure-ai-agent-access' ); ?></option>
                <option value="revoke"><?php esc_html_e( 'Revoke', 'secure-ai-agent-access' ); ?></option>
            </select>
            <input type="submit" class="button action" value="<?php esc_attr_e( 'Apply', 'secure-ai-agent-access' ); ?>">
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('.saia-view-full-link').on('click', function(e) {
        e.preventDefault();
        var link = $(this).data('link');
        prompt('Full link:', link);
    });
    
    $('.saia-copy-link').on('click', function(e) {
        e.preventDefault();
        var link = $(this).data('link');
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(link).then(function() {
                alert('Link copied to clipboard!');
            });
        } else {
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(link).select();
            document.execCommand('copy');
            $temp.remove();
            alert('Link copied to clipboard!');
        }
    });
});
</script>