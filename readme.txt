=== Unnotifier — disable admin notices individually ===
Contributors: alexkovalevv, wpaifactory
Donate link: https://wp-aifactory.com
Tags: disable admin notices, disable notices, hide notifications, dashboard cleanup, notice control
Requires at least: 5.0
Tested up to: 7.1
Requires PHP: 7.4
Tested up to PHP: 8.4
Stable tag: 1.2.9
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Disable admin notices individually or completely. Smart plugin detection, flexible modes, clean dashboard cleanup. Free & lightweight solution.

== Description ==

**Unnotifier** helps you disable admin notices with complete control and flexibility. Tired of intrusive premium upgrade notices, promotional messages, and endless stream of admin notifications? Unnotifier solves this problem once and for all with powerful features to disable admin notices individually or completely.

**Inspired by the popular plugin Disable Admin Notices individually, but Unnotifier surpasses it in stability, functionality, and support. All features of the original plugin are implemented here, but with improved architecture and regular updates.**

= Key Features to Disable Admin Notices =

* **Three Display Modes:**
  * Show all notifications (default WordPress behavior)
  * Individual control (recommended) — adds hide buttons to each notice
  * Hide all notifications completely

* **Dual Hide Options:** Each admin notice gets two discrete buttons:
  * **"Hide for me"** — Hide notice only for current user
  * **"Hide for all"** — Hide notice for all users (admin only)
* **Smart Plugin Detection:** Automatically identifies which plugin or theme generates each notification
* **Extended Metadata:** Stores information about hidden notices with plugin names and content
* **Easy Reset:** Restore all hidden notices with one click in settings
* **Clean Interface:** Minimalistic design that doesn't interfere with your workflow
* **Lightweight:** No bloat, just essential functionality
* **Accessibility Ready:** Full keyboard navigation and screen reader support
* **AJAX Technology:** Smooth notice hiding without page reload
* **Security:** All operations protected with nonce tokens and permission checks

= How to Disable Admin Notices =

1. **Install and activate** the plugin to disable admin notices
2. **Choose your mode** in Settings → Unnotifer:
   - **Show all notifications:** Normal behavior
   - **Individual control:** Adds hide buttons to each notice (recommended)
   - **Hide all:** Completely removes all admin notices
3. **Hide notices** by clicking "Hide for me" or "Hide for all" buttons on any notification
4. **Reset hidden notices** anytime from the settings page

= Advanced Functionality =

**Smart Plugin Detection:** The plugin uses advanced algorithms to identify the source of each notification:
* Call stack analysis (debug_backtrace)
* PHP reflection for callback function analysis
* Plugin name extraction from file headers
* Result caching for performance optimization

**Notice Metadata:** Each hidden notice is saved with detailed information:
* Source plugin name
* Full notice content
* Brief description (excerpt)
* Hide time and user ID

**Plugin Architecture:** Built on SOLID principles using:
* PSR-4 class autoloading
* Singleton pattern for state management
* Interfaces for all core components
* Separation of responsibilities between classes

= Perfect for Dashboard Cleanup =

* **Site administrators** who want to disable admin notices for a cleaner dashboard
* **Developers** working on multiple sites with many plugins
* **Agencies** managing client websites
* **Anyone** tired of notification overload
* **E-commerce owners** with multiple WooCommerce plugins
* **SEO specialists** using many optimization tools

= 💼 Use Cases & Practical Applications =

The plugin is useful in various situations, from personal use to managing complex multisite networks.

**🎯 Individual Notice Control**
Perfect for getting rid of individual annoying notifications (premium version ads, offers, unnecessary reminders) without losing important system messages. Disable admin notices selectively with two buttons on each notice: "Hide for me" (personal hiding) and "Hide for all" (global hiding for all users). You decide which notifications you want to see and which ones clutter your workspace. The plugin intelligently identifies the source of each notification, making it easy to understand which plugin is generating intrusive messages.

**🧹 Complete Dashboard Cleanup**
Disable admin notices globally (except plugin update warnings) for absolute focus. When you need maximum concentration on work, activate the "Hide all" mode and your admin panel becomes completely clean. This is especially useful during development, content editing, or any work that requires full attention. All hidden notifications are saved in the database, so you can restore them at any time with one click.

**👥 Working with Client Sites**
Creating a clean and understandable interface for clients by removing technical messages that might confuse or scare them. Agencies and freelancers can prepare a professional admin panel where clients see only what they need. Hide developer notifications, debug messages, and technical warnings while keeping important content management notifications visible. This significantly improves the user experience for non-technical clients.

**🌐 Team Work & Multisite Networks**
Hiding notifications for other users or the entire network while keeping them visible only to the administrator. In Multisite networks, you can manage notifications centrally: hide specific messages for all subsites or configure individual settings for each site. This is especially useful for large projects with multiple administrators where notification coordination is important.

**📊 Advanced Notice Management**
The plugin stores detailed metadata about each hidden notification: source plugin name, full content, excerpt, hiding time and user ID. This allows you to analyze which plugins generate the most notifications and make informed decisions about their use. You can always review the list of hidden notices and restore specific ones if needed.

**🔧 Development & Staging Environments**
Ideal for developers working with test sites and staging servers. Disable admin notices during development to focus on debugging and testing. Easily switch between modes depending on the work stage: show all notifications during initial setup, use individual control during active development, and hide all during client demonstrations.

**🛒 E-commerce & WooCommerce**
Online stores often have dozens of plugins generating notifications: WooCommerce itself, payment systems, shipping plugins, marketing tools. Unnotifier helps organize this chaos by allowing you to selectively hide promotional messages while keeping important transactional notifications. This is especially useful for stores with many extensions and add-ons.

**⚡ Performance & Productivity**
By choosing to disable admin notices that distract you, you improve productivity and work speed. The clean interface helps focus on important tasks, and the smart plugin detection system shows exactly which plugins are generating notifications. You can make informed decisions about which plugins to keep and which to replace with less intrusive alternatives.

= Privacy & Performance =

* **No external requests** — everything works locally
* **Minimal database usage** — only stores hidden notice IDs and metadata
* **No tracking** — your data stays on your server
* **Lightweight code** — won't slow down your admin area
* **Optimized queries** — caching of plugin detection results
* **Security** — all AJAX requests protected with nonce tokens


= How to Install Plugin =

**Installation Steps:**
1. Go to your admin area
2. Navigate to Plugins → Add New
3. Search for "Unnotifier"
4. Click "Install Now" and then "Activate"

**Plugin Setup to Disable Admin Notices:**
1. Go to Settings → Unnotifer
2. Choose your preferred display mode:
   - **Show all notifications:** Default behavior
   - **Individual control:** Adds hide buttons (recommended)
   - **Hide all:** Removes all admin notices
3. Configure additional settings if needed

**Using the Plugin:**
1. Navigate to any admin page with notifications
2. Click "Hide for me" to hide notice for yourself only
3. Click "Hide for all" to hide notice for all users (admin only)
4. Hidden notices disappear immediately with smooth animation

**Resetting Hidden Notices:**
1. Go to Settings → Unnotifer
2. Click "Reset All Hidden Notices" button
3. All previously hidden notices will be restored
4. You can also reset notices for specific users if needed

== Installation ==

= Automatic Installation =

1. Go to your WordPress admin area
2. Navigate to Plugins → Add New
3. Search for "Unnotifier"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin zip file
2. Upload it to your `/wp-content/plugins/` directory
3. Extract the files
4. Activate the plugin through the 'Plugins' menu in WordPress

= After Installation =

1. Go to Settings → Unnotifer
2. Choose your preferred display mode
3. Start hiding notices by clicking "Hide for me" or "Hide for all" buttons

== Frequently Asked Questions ==

= Will this affect important core notifications? =

The plugin gives you control over which notices to disable. Important security and update notifications will still appear unless you specifically choose to hide them or select "Hide all notifications" mode. You can disable admin notices selectively while keeping critical messages visible.

= Can I restore hidden notices? =

Yes! Go to Settings → Unnotifer and click "Reset All Hidden Notices" to restore all previously hidden notifications. This flexibility lets you disable admin notices temporarily and restore them when needed.

= Does this work with all plugins? =

The plugin works with the vast majority of admin notices from plugins and themes. Some plugins that use non-standard notification methods might not be affected.

= Will hidden notices stay hidden after plugin updates? =

Yes, your hidden notice preferences are stored in the database and will persist through plugin updates.

= Does this slow down my admin area? =

No, the plugin is designed to be lightweight and only loads its functionality in the admin area when needed.

= Can I hide notices for specific users only? =

Currently, the plugin applies settings globally for all users with admin access. User-specific settings may be added in future versions.

== Screenshots ==

1. **Notice Hiding Example** - Shows admin notices with "Hide for me" and "Hide for all" buttons, demonstrating how the plugin adds control options to each notification
2. **Plugin Settings Page** - Configuration interface where you can choose display modes, reset hidden notices, and manage plugin options
3. **Settings Page Overview** — Screenshot of the plugin settings page with example configuration options
4. **Notifications Panel Overview** – This is an overview of the panel where all hidden notifications are collected in the admin bar, allowing you to view and restore them.

== Our Other Plugins ==

**[AI Thumbnails Maker](https://wordpress.org/plugins/ai-thumbnails-maker/)** - Automatically generate beautiful AI-powered thumbnails and force regenerate featured images for your WordPress posts. Perfect for keeping your media library fresh and professional.
**[SmartyPress AI Engine](https://wordpress.org/plugins/smartypress-ai-engine/)** - Powerful AI integration for WordPress with ChatGPT and Deepseek. Generate high-quality content directly in Gutenberg editor with Magic Wand inline editing and AI Content Generator panel. Create titles, articles, excerpts with 10+ pre-built actions or custom prompts.
**[AdsDestroyer – disable admin ad & adblocker](https://wordpress.org/plugins/ads-destroyer/)** - Transform your WordPress admin into a clean, distraction-free workspace. Remove unwanted notices, promotional banners, and clutter with visual selection. Perfect for maintaining focus and creating professional client experiences.

== Changelog ==

= 1.2.9 =
* **WordPress 7.1 Compatibility**: Verified compatibility with WordPress 7.1 and updated plugin metadata.
* **Notice Callback Compatibility**: Prevented fatal errors when third-party admin notice callbacks require arguments that WordPress notice hooks do not provide.

= 1.2.8 =
* **WordPress 7.0 Compatibility**: Verified compatibility with WordPress 7.0 and updated plugin metadata.
* **Release Metadata**: Updated stable tag and tested WordPress version for the latest release.

= 1.2.7 =
* **Fixed Elementor Layout**: Corrected flexbox layout by injecting buttons inside .e-notice container instead of wrapping it
* **Elementor Styling**: Added Roboto font and Elementor CSS variables for seamless visual integration
* **Disable Comments Support**: Added flex-wrap fix for Disable Comments plugin notices (.disable__comment__alert)
* **Better WordPress Compatibility**: Buttons now stay with notices when WordPress moves .notice elements via jQuery
* **CSS Improvements**: Added box-sizing and proper flex-wrap for all flex-based notice containers

= 1.2.6 =
* **NEW: Elementor Notice Integration**: Added special handling for Elementor plugin notices with custom e-notice structure
* **Fixed Layout Issue**: Resolved flexbox layout conflict where Unnotifier buttons appeared as a column alongside Elementor notice content
* **Improved Notice Wrapping**: Elementor notices now properly wrapped with buttons displayed above the notice content
* **Enhanced CSS Styling**: Added dedicated styles for Elementor notices with proper visual integration
* **Better Compatibility**: Improved support for plugins using custom notice structures with display: flex layouts
* **Code Quality**: Added new wrap_elementor_notice() method for cleaner architecture and better maintainability

= 1.2.4 =
* **WordPress 6.9 Compatibility**: Added full compatibility and testing support for WordPress 6.9

= 1.2.3 =
* **NEW: Tabbed Settings Interface**: Added tab navigation to settings page with "General Settings" and "Hidden Notices" tabs
* **NEW: Hidden Notices Management Page**: Created dedicated page with WordPress-style list table for managing hidden notices

= 1.2.2 =
* **NEW: Admin Bar Panel**: Added admin bar dropdown panel showing all hidden notices with quick restore functionality
* **Admin Bar Counter**: Badge counter shows total number of hidden notices
* **Individual Restore**: Restore notices directly from admin bar without going to settings page
* **Enhanced UI**: Beautiful admin bar panel with categorized notices (hidden for you, hidden for all)
* **Improved UX**: Hover effects, smooth animations, and better visual feedback
* **Settings Option**: New setting to enable/disable admin bar panel functionality
* **Performance**: Optimized admin bar panel loading and AJAX operations

= 1.2.1 =
* **PHP 8.4 Support**: Fully tested and compatible with PHP versions 7.4 through 8.4
* **Fixed Plugin Prefix Issue**: Changed plugin prefix from "unn" to "unno" to comply with WordPress.org requirements
* **Improved Notice Metadata**: Enhanced storage and display of notice metadata including plugin names and content
* **Better Plugin Detection**: Improved algorithm for detecting which plugin generates each notice
* **Enhanced Settings Interface**: Better display of hidden notices with proper plugin names and content excerpts
* **Code Quality Improvements**: Updated all CSS classes, JavaScript selectors, and PHP functions to use new prefix
* **WordPress Standards Compliance**: Full compliance with WordPress coding standards and plugin directory requirements
* **Type Safety Improvements**: Fixed exception handling for better PHP 8.x compatibility
* **Input Validation**: Enhanced input validation in settings sanitization for PHP 8.x strict types
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

== Contributing ==

We welcome contributions! If you'd like to contribute to the development of this plugin:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

== Privacy Policy ==

This plugin does not collect, store, or transmit any personal data. All functionality works locally on your WordPress installation. Hidden notice preferences are stored in your WordPress database only.

== Technical Details ==

* **PHP Version:** 7.4 - 8.4 (fully tested and compatible)
* **WordPress Version:** 5.0 - 7.1
* **Database Tables:** Uses WordPress options table only
* **External Dependencies:** None
* **Multisite Compatible:** Yes
* **Translation Ready:** Yes (translations welcome!)
* **OOP Architecture:** Modern object-oriented design with PSR-4 autoloading
* **Type Safety:** Full type declarations for PHP 7.4+ compatibility

== Important Notice About Caching ==

**Output Buffering Usage:** This plugin uses output buffering (ob_start()) only in the WordPress admin area to capture and process admin notices. This functionality is NOT used on the frontend of your website.

**Server Caching Compatibility:** If you experience issues with admin area caching or if your hosting provider uses server-based caching (such as nginx, Varnish, or similar services), please note that this plugin may conflict with such caching systems. In such cases, we recommend not using this plugin.

**WordPress Managed Hosting:** Some managed WordPress hosting providers may prohibit plugins that use output buffering due to potential conflicts with their caching infrastructure. Please check with your hosting provider before using this plugin if you're on a managed WordPress hosting service.

== Plugin Detection Feature ==

**debug_backtrace() Usage:** This plugin uses PHP's debug_backtrace() function to detect which plugin generates each admin notice. This feature is enabled by default but can be disabled in the plugin settings under "Show plugin names in notices?".

**Performance Considerations:** The debug_backtrace() function may have a slight performance impact on high-traffic sites. If you experience performance issues, you can disable this feature in the plugin settings. When disabled, notices will show "Unknown Plugin" instead of the actual plugin name.

**Technical Details:** The plugin analyzes the call stack to identify plugin files and extracts plugin names from their headers. This helps users identify the source of notifications for better management.

== Inspiration & Credits ==

This plugin was inspired by and builds upon ideas from several community projects that helped pioneers the ability to disable admin notices:

**Disable Admin Notices individually**, **Hide Dashboard Notifications**, **WP Hide Plugin Updates**, **Hide Admin Notices**, **WP Notification Center**

We studied these plugins, learned from their approaches, and created Unnotifier with modern architecture, enhanced flexibility, and active maintenance. Special thanks to their developers for pioneering admin notice management solutions.

**Full Plugin Review:** For a comprehensive overview and comparison with alternatives, read our detailed article at [Wp Ai Factory - Effortlessly Disable Admin Notices in WordPress](https://wp-aifactory.com/effortlessly-disable-admin-notices-in-wordpress-unnotifier-free/)

== Credits ==

Developed with ❤️ for the WordPress community.

Special thanks to all beta testers and contributors who helped make this plugin better.