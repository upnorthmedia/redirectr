# Redirectr - 301 Redirects & Broken Link Monitor

A lightweight redirect manager and broken link monitor for WordPress. Create 301 redirects and track 404 errors with a clean, intuitive interface.

## Quick Install

1. Download [redirectr.zip](./redirectr.zip) from this repository
2. In your WordPress admin, go to **Plugins → Add New → Upload Plugin**
3. Click **Choose File**, select the `redirectr.zip` you downloaded
4. Click **Install Now**, then **Activate Plugin**
5. Go to **Redirectr** in your admin menu to start managing redirects

That's it! The plugin begins monitoring for broken links automatically.

## Description

**Redirectr** is a simple yet powerful WordPress redirect plugin that helps you manage URL redirections and monitor broken links on your site. Whether you've migrated from another platform, restructured your content, or just need to fix outdated links, Redirectr makes it easy to keep your visitors and search engines happy.

### Why Use Redirectr?

Broken links frustrate visitors and hurt your SEO. When someone clicks a link that leads nowhere, they leave. Search engines notice this too, potentially lowering your rankings. Redirectr solves both problems by:

- **Tracking broken links automatically** - See exactly which URLs visitors are trying to access that don't exist
- **Creating redirects in seconds** - Point old URLs to new destinations with just a few clicks
- **Preserving SEO value** - 301 redirects pass link equity to your new pages

### Perfect For Site Migrations

Moving to WordPress from another platform? Changing your permalink structure? Consolidating content? Site migrations often break hundreds of existing links. Redirectr helps you:

- Identify which old URLs visitors and search engines are still trying to access
- Create redirects directly from the broken link log with one click
- Track hit counts to prioritize which redirects matter most
- Support both exact URL matching and regex patterns for bulk redirects

### Key Features

- **Simple Redirect Management** - Add, edit, and organize 301, 302, and 307 redirects
- **Broken Link Monitoring** - Automatic logging of 404 errors with hit counts and referrer data
- **One-Click Redirect Creation** - Convert any broken link into a redirect instantly
- **Regex Support** - Create pattern-based redirects for complex URL structures
- **Performance Optimized** - Built-in caching ensures minimal impact on page load times
- **Clean Admin Interface** - Intuitive design that fits seamlessly with WordPress
- **Privacy Focused** - IP addresses are hashed, never stored in plain text

### How It Works

1. **Install and activate** - Redirectr starts monitoring for broken links immediately
2. **Review broken links** - Check the "Broken Links" page to see what's not working
3. **Create redirects** - Click "Create Redirect" on any broken link and enter the destination
4. **Monitor and maintain** - Track redirect usage and manage everything from one place

### Use Cases

- **Website migrations** - Redirect old URLs after moving to WordPress or changing hosts
- **Content restructuring** - Update URLs when reorganizing categories or pages
- **Domain changes** - Redirect paths when moving to a new domain
- **Fixing typos** - Redirect commonly mistyped URLs to the correct pages
- **Campaign tracking** - Create short, memorable URLs that redirect to landing pages
- **Removing old content** - Redirect deleted pages to relevant alternatives

## Alternative Installation (Manual)

For developers or advanced users:

1. Upload the `redirectr` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Redirectr** in your admin menu to start managing redirects

## Frequently Asked Questions

### What's the difference between 301 and 302 redirects?

A 301 redirect is permanent and tells search engines to transfer SEO value to the new URL. Use this for content that has permanently moved. A 302 redirect is temporary and should be used when content will return to the original URL eventually.

### Will this slow down my website?

No. Redirectr uses WordPress object caching to minimize database queries. Redirects are checked efficiently before WordPress loads the full page, so there's virtually no performance impact.

### Can I redirect to external URLs?

Yes. You can redirect to any valid URL, whether it's on your site or an external domain.

### What are regex redirects?

Regular expression (regex) redirects let you create pattern-based rules. For example, you could redirect all URLs matching `/old-blog/(.*)` to `/blog/$1`, automatically handling hundreds of URLs with a single rule.

### How do I migrate redirects from another plugin?

Currently, redirects must be added manually or via the broken link conversion feature. Import/export functionality is planned for a future release.

### Does this work with custom post types?

Yes. Redirectr works at the URL level, so it handles any URL structure including custom post types, taxonomies, and custom rewrite rules.

### How long are broken link logs kept?

By default, ignored broken links older than 30 days are automatically cleaned up. You can adjust this in the Settings page.

### Is this compatible with caching plugins?

Yes. Redirectr performs redirects before content is generated, so it works correctly with all major caching plugins including WP Super Cache, W3 Total Cache, and WP Rocket.

## Changelog

### 1.0.1
- Added default exclusion for `/.well-known/` directory
- Added default exclusion for image file extensions (png, jpg, jpeg, gif, webp, svg, bmp, tiff, avif)

### 1.0.0
- Initial release
- 301, 302, and 307 redirect support
- Broken link (404) monitoring and logging
- One-click redirect creation from broken links
- Exact match and regex pattern support
- Hit count tracking for redirects and broken links
- Referrer logging for broken links
- Configurable log retention
- Object caching for performance
- Privacy-focused IP hashing

## Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher

## License

GPLv2 or later - https://www.gnu.org/licenses/gpl-2.0.html
