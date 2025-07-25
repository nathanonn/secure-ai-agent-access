<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Get all users except the current logged-in user
$current_user_id = get_current_user_id();
$users = get_users( array(
    'orderby' => 'display_name',
    'order' => 'ASC',
    'exclude' => array( $current_user_id ),
) );

$settings = get_option( 'saia_settings' );
?>

<div class="wrap">
    <h1><?php esc_html_e( 'Generate Magic Link', 'secure-ai-agent-access' ); ?></h1>

    <p>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=saia-dashboard' ) ); ?>">
            <?php esc_html_e( '← Back to Dashboard', 'secure-ai-agent-access' ); ?>
        </a>
    </p>

    <div class="saia-card">
        <h2><?php esc_html_e( 'Generate Magic Link', 'secure-ai-agent-access' ); ?></h2>

        <form id="saia-generate-link-form" method="post">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="saia-user-select"><?php esc_html_e( 'Select User Account', 'secure-ai-agent-access' ); ?></label>
                    </th>
                    <td>
                        <select name="user_id" id="saia-user-select" required>
                            <option value=""><?php esc_html_e( 'Select a user...', 'secure-ai-agent-access' ); ?></option>
                            <?php foreach ( $users as $user ) :
                                // Include all users (including administrators) except the current logged-in user
                                $role_names = array_map( function( $role ) {
                                    $wp_roles = wp_roles();
                                    return isset( $wp_roles->role_names[ $role ] ) ? translate_user_role( $wp_roles->role_names[ $role ] ) : $role;
                                }, $user->roles );
                            ?>
                                <option value="<?php echo esc_attr( $user->ID ); ?>">
                                    <?php echo esc_html( $user->display_name . ' (' . implode( ', ', $role_names ) . ')' ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e( 'Select a user account (including administrators) to generate a magic link for AI agent access. You cannot generate a link for your own account.', 'secure-ai-agent-access' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Session Duration', 'secure-ai-agent-access' ); ?></th>
                    <td>
                        <div class="saia-settings-info">
                            <p>
                                <strong><?php esc_html_e( 'Maximum:', 'secure-ai-agent-access' ); ?></strong>
                                <?php
                                $duration = $settings['max_session_duration'];
                                if ( $duration < 3600 ) {
                                    echo esc_html( sprintf( __( '%d minutes', 'secure-ai-agent-access' ), round( $duration / 60 ) ) );
                                } elseif ( $duration < 86400 ) {
                                    echo esc_html( sprintf( __( '%d hours', 'secure-ai-agent-access' ), round( $duration / 3600 ) ) );
                                } else {
                                    echo esc_html( sprintf( __( '%d days', 'secure-ai-agent-access' ), round( $duration / 86400 ) ) );
                                }
                                ?>
                                <?php esc_html_e( '(default)', 'secure-ai-agent-access' ); ?>
                            </p>
                            <p>
                                <strong><?php esc_html_e( 'Inactivity timeout:', 'secure-ai-agent-access' ); ?></strong>
                                <?php echo esc_html( sprintf( __( '%d minutes', 'secure-ai-agent-access' ), round( $settings['inactivity_timeout'] / 60 ) ) ); ?>
                            </p>
                            <p>
                                <strong><?php esc_html_e( 'Link expires if unused:', 'secure-ai-agent-access' ); ?></strong>
                                <?php
                                $expiration = $settings['unused_link_expiration'];
                                if ( $expiration < 86400 ) {
                                    echo esc_html( sprintf( __( '%d hours', 'secure-ai-agent-access' ), round( $expiration / 3600 ) ) );
                                } else {
                                    echo esc_html( sprintf( __( '%d days', 'secure-ai-agent-access' ), round( $expiration / 86400 ) ) );
                                }
                                ?>
                            </p>
                        </div>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary">
                    <?php esc_html_e( 'Generate Magic Link', 'secure-ai-agent-access' ); ?>
                </button>
            </p>
        </form>

        <div id="saia-link-result"></div>
    </div>
</div>