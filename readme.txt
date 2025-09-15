=== Unnotifier — disable admin notices individually ===
Contributors: alexkovalevv
Donate link: https://wordpress.org/support/plugin/unnotifier/
Tags: admin, notices, hide, disable, dashboard
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Clean up your WordPress admin dashboard by hiding notices individually or all at once. Each notice gets a "hide forever" button for control.

== Description ==

**Unnotifier** gives you complete control over admin notifications in your WordPress dashboard. Stop being overwhelmed by plugin update notices, promotional messages, and other admin notifications that clutter your workspace.

= Key Features =

* **Three Display Modes:**
  * Show all notifications (default WordPress behavior)
  * Hide notifications individually (recommended)
  * Hide all notifications completely

* **Individual Control:** Each admin notice gets a discrete "×" button to hide it forever
* **Easy Reset:** Restore all hidden notices with one click in settings
* **Clean Interface:** Minimal, non-intrusive design that doesn't interfere with your workflow
* **Lightweight:** No bloat, just the essential functionality you need
* **Accessibility Ready:** Full keyboard navigation and screen reader support

= How It Works =

1. **Install and activate** the plugin
2. **Choose your mode** in Settings → Admin Notices:
   - **Show all notifications:** Normal WordPress behavior
   - **Hide individually:** Adds hide buttons to each notice (recommended)
   - **Hide all:** Completely removes all admin notices
3. **Hide notices** by clicking the "×" button on any notification
4. **Reset hidden notices** anytime from the settings page

= Perfect For =

* **Site administrators** who want a cleaner dashboard
* **Developers** working on multiple sites with many plugins
* **Agencies** managing client websites
* **Anyone** tired of notification overload

= Privacy & Performance =

* **No external requests** - everything works locally
* **Minimal database usage** - only stores hidden notice IDs
* **No tracking** - your data stays on your server
* **Lightweight code** - won't slow down your admin area

== Installation ==

= Automatic Installation =

1. Go to your WordPress admin area
2. Navigate to Plugins → Add New
3. Search for "Disable Admin Notices Individually"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin zip file
2. Upload it to your `/wp-content/plugins/` directory
3. Extract the files
4. Activate the plugin through the 'Plugins' menu in WordPress

= After Installation =

1. Go to Settings → Admin Notices
2. Choose your preferred display mode
3. Start hiding notices by clicking the "×" button on any notification

== Frequently Asked Questions ==

= Will this affect important WordPress core notifications? =

The plugin gives you control over which notices to hide. Important security and update notifications from WordPress core will still appear unless you specifically choose to hide them or select "Hide all notifications" mode.

= Can I restore hidden notices? =

Yes! Go to Settings → Admin Notices and click "Reset All Hidden Notices" to restore all previously hidden notifications.

= Does this work with all plugins? =

The plugin works with the vast majority of admin notices from plugins and themes. Some plugins that use non-standard notification methods might not be affected.

= Will hidden notices stay hidden after plugin updates? =

Yes, your hidden notice preferences are stored in the database and will persist through plugin updates.

= Does this slow down my admin area? =

No, the plugin is designed to be lightweight and only loads its functionality in the admin area when needed.

= Can I hide notices for specific users only? =

Currently, the plugin applies settings globally for all users with admin access. User-specific settings may be added in future versions.

== Screenshots ==

1. **Settings Page** - Choose between three display modes and reset hidden notices
2. **Individual Hide Buttons** - Each notice gets a discrete "×" button for hiding
3. **Clean Dashboard** - Your admin area after hiding unwanted notices
4. **Before and After** - See the difference in dashboard cleanliness

== Changelog ==

= 1.2.0 =
* **Fixed Plugin Prefix Issue**: Changed plugin prefix from "unn" to "unno" to comply with WordPress.org requirements
* **Improved Notice Metadata**: Enhanced storage and display of notice metadata including plugin names and content
* **Better Plugin Detection**: Improved algorithm for detecting which plugin generates each notice
* **Enhanced Settings Interface**: Better display of hidden notices with proper plugin names and content excerpts
* **Code Quality Improvements**: Updated all CSS classes, JavaScript selectors, and PHP functions to use new prefix
* **WordPress Standards Compliance**: Full compliance with WordPress coding standards and plugin directory requirements
* **Bug Fixes**: Fixed issues with notice display and metadata storage
* **Performance Optimizations**: Improved loading and processing of admin notices

= 1.1.0 =
* **Enhanced Plugin Detection**: Improved algorithm for detecting which plugin generates each notice
* **Stack Trace Analysis**: Added advanced stack trace analysis to identify plugin source files
* **Reflection-Based Detection**: Implemented reflection API for better callback analysis
* **Plugin Name Extraction**: Enhanced extraction of plugin names from file paths and headers
* **Fallback Handling**: Better handling of unknown plugins with "Unknown Plugin" label
* **Performance Optimization**: Added caching for plugin detection results
* **Code Refactoring**: Complete refactoring from DANI to UNN namespace and prefixes
* **Security Improvements**: Enhanced nonce verification and input sanitization
* **Bug Fixes**: Fixed various edge cases in plugin detection and notice handling
* **WordPress 6.8 Compatibility**: Updated for latest WordPress version

= 1.0.0 =
* Initial release
* Three display modes: show all, hide individually, hide all
* Individual hide buttons for each notice
* Settings page with reset functionality
* AJAX-powered hiding with smooth animations
* Accessibility features and keyboard support
* Responsive design for mobile admin
* WordPress 6.4 compatibility

== Upgrade Notice ==

= 1.2.0 =
Important update with WordPress.org compliance fixes! Changed plugin prefix to meet requirements, improved notice metadata display, and enhanced plugin detection. Includes better settings interface and performance optimizations.

= 1.1.0 =
Major update with enhanced plugin detection! Now you can see exactly which plugin generates each notice, making it easier to manage your admin dashboard. Includes improved performance, better security, and WordPress 6.8 compatibility.

= 1.0.0 =
Initial release of Unnotifier. Clean up your WordPress admin dashboard today!

== Support ==

For support, feature requests, or bug reports, please visit:

* **Plugin Support Forum:** https://wordpress.org/support/plugin/unnotifier/
* **Documentation:** Available in the plugin settings page
* **GitHub Repository:** [Link to your GitHub repo if available]

== Contributing ==

We welcome contributions! If you'd like to contribute to the development of this plugin:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

== Privacy Policy ==

This plugin does not collect, store, or transmit any personal data. All functionality works locally on your WordPress installation. Hidden notice preferences are stored in your WordPress database only.

== Technical Details ==

* **Minimum PHP Version:** 7.4
* **Minimum WordPress Version:** 5.0
* **Database Tables:** Uses WordPress options table only
* **External Dependencies:** None
* **Multisite Compatible:** Yes
* **Translation Ready:** Yes (translations welcome!)

== Important Notice About Caching ==

**Output Buffering Usage:** This plugin uses output buffering (ob_start()) only in the WordPress admin area to capture and process admin notices. This functionality is NOT used on the frontend of your website.

**Server Caching Compatibility:** If you experience issues with admin area caching or if your hosting provider uses server-based caching (such as nginx, Varnish, or similar services), please note that this plugin may conflict with such caching systems. In such cases, we recommend not using this plugin.

**WordPress Managed Hosting:** Some managed WordPress hosting providers may prohibit plugins that use output buffering due to potential conflicts with their caching infrastructure. Please check with your hosting provider before using this plugin if you're on a managed WordPress hosting service.

== Plugin Detection Feature ==

**debug_backtrace() Usage:** This plugin uses PHP's debug_backtrace() function to detect which plugin generates each admin notice. This feature is enabled by default but can be disabled in the plugin settings under "Show plugin names in notices?".

**Performance Considerations:** The debug_backtrace() function may have a slight performance impact on high-traffic sites. If you experience performance issues, you can disable this feature in the plugin settings. When disabled, notices will show "Unknown Plugin" instead of the actual plugin name.

**Technical Details:** The plugin analyzes the call stack to identify plugin files and extracts plugin names from their headers. This helps users identify the source of notifications for better management.

== Credits ==

Developed with ❤️ for the WordPress community.

Special thanks to all beta testers and contributors who helped make this plugin better.