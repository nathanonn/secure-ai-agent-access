=== Secure AI Agent Access ===
Contributors: nathanonn
Tags: security, AI, authentication, magic-links, temporary-access
Requires at least: 5.0
Tested up to: 6.8.2
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Provides administrators with a secure method to grant AI agents temporary, controlled access to their WordPress sites using single-use magic links.

== Description ==

Secure AI Agent Access is a WordPress plugin that eliminates the security risks of sharing permanent credentials with AI services. Instead, it provides single-use magic links that grant time-limited access to designated user accounts.

= Key Features =

* **Single-Use Magic Links**: Each link expires immediately after first use
* **Time-Limited Sessions**: Configurable session durations with automatic timeout
* **Emergency Kill Switch**: Instantly terminate all AI agent sessions
* **Multi-Site Support**: Works seamlessly across WordPress networks
* **Role-Based Access**: Leverages WordPress's built-in permission system
* **Clean Data Management**: Automatic cleanup of expired data
* **Zero Dependencies**: Standalone solution with no external requirements

= Security Features =

* You cannot generate magic links for your own account
* Configurable inactivity timeouts
* Optional IP restrictions
* Rate limiting capabilities
* Complete session tracking
* Secure token generation

= Perfect For =

* WordPress administrators managing multiple sites
* Digital agencies using AI tools for content management
* E-commerce stores automating product updates
* Anyone needing to grant temporary, secure access to AI services

== Installation ==

1. Upload the `secure-ai-agent-access` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'AI Agent Access' in your admin menu
4. Configure your security settings
5. Start generating magic links for AI agents

== Frequently Asked Questions ==

= How do magic links work? =

Magic links are single-use URLs that authenticate a specific user account when accessed. Once used, the link becomes invalid and cannot be reused.

= Can administrators be accessed via magic links? =

Yes. Administrator accounts can be selected when generating magic links. However, you cannot generate a magic link for your own account (the currently logged-in user).

= What happens if a session times out? =

Sessions automatically terminate after the configured inactivity period or maximum duration. The AI agent will be logged out and must use a new magic link.

= Does this work with multisite? =

Yes! The plugin fully supports WordPress multisite networks with network-wide management capabilities.

= How is data cleaned up? =

The plugin automatically cleans up expired links and old session data based on your configured retention periods. You can also manually trigger cleanup at any time.

== Changelog ==

= 1.0.0 =
* Initial release
* Single-use magic link generation
* Configurable session timeouts
* Emergency kill switch
* Multi-site support
* Automatic data cleanup
* Complete uninstall cleanup

== Upgrade Notice ==

= 1.0.0 =
Initial release of Secure AI Agent Access.

== Security ==

This plugin has been developed with security as the top priority:

* All inputs are properly sanitized
* All outputs are escaped
* Nonces are used for all forms and AJAX requests
* Database queries use prepared statements
* File operations validate file types
* User capabilities are always checked

If you discover a security issue, please report it to [security contact].

== Privacy ==

This plugin stores:
* User IDs associated with magic links
* IP addresses for security tracking
* Session timestamps and activity

All data is stored locally in your WordPress database and is never transmitted to external services. Complete data removal is performed when the plugin is uninstalled.