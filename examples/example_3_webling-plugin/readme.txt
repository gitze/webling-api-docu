=== Webling ===
Contributors: usystemsgmbh
Donate link: https://www.webling.eu
Tags: webling, vereinssoftware, vereinsverwaltung, verein, mitglieder, anmeldung, formular, anmeldeformular
Requires at least: 4.6
Tested up to: 6.5
Stable tag: 3.9.0
License: Apache License
License URI: http://www.apache.org/licenses/LICENSE-2.0.html

Anmeldeformulare und Mitgliederdaten aus der Vereinssoftware webling.eu auf deiner Webseite anzeigen.

== Description ==

Zeige Mitgliederdaten aus Webling auf deiner Webseite an oder erstelle ein Anmeldeformular, welches dir automatisch Mitglieder in deinem Webling erstellt.

= Mitgliederlisten =

Zeige eine Mitgliederliste mit Daten aus der Vereinssoftware Webling auf deiner Webseite an. Es können entweder alle Mitglieder angezeigt werden, oder nach bestimmten Gruppen gefiltert.

= Anmeldeformuare =

Erstelle ein Anmeldeformular, über welches sich Mitglieder anmelden können. Es wird automatisch ein Mitglied mit den angegebene Daten in Webling erstellt. Die Formulare lassen sich so konfigurieren, dass nur gewünschte Felder angezeigt werden.

= Webling =

Webling ist eine praktische Vereinsverwaltungssoftware. Du benötigst mindestens ein <a href="https://www.webling.eu/angebote.php">Webling Basic</a> oder höher um dieses Plugin zu nutzen. Die benötigte API ist im Free Abo nicht verfügbar. Das Plugin kann nicht ohne Webling benutzt werden.

= Support =

Bei Fragen zum Plugin wenden sie sich bitte an support@webling.ch

== Installation ==

Der Webling API-Key muss in den Einstellungen in der WordPress Administration hinterlegt werden. ("Webling" > "Einstellungen").

== Upgrade Notice ==

= 3.0 =
Das Shortcode Format hat sich geändert. Shortcodes wurden (wo möglich) automatisch konvertiert.
Das Format [webling_memberlist groups="123,567"] wird nicht mehr unterstützt (mit dem "groups" Attribut).

== Frequently Asked Questions ==

n/a

== Screenshots ==

1. Mitgliederliste auf einer Seite
2. Konfiguration einer Mitgliederliste
3. Mitgliederlisten
4. Anmeldeformular auf einer Seite
5. Konfiguration der Felder eines Anmeldeformulares
6. Einstellungen eines Anmeldeformulares
7. Plugin Einstellungen im Admin Bereich

== Changelog ==

= 3.9.0 =
* Disabled the CAPTCHA 4WP option. It did not work for a while because CAPTCHA 4WP moved to a paid plan and changed their API. Use Friendly Captcha instead.
* The memberlist now formats email addresses as mailto links.
* Settings: Better input validation/automatic correction of webling hostname
* Bugfix: Sorting a memberlist by multienum fields did not work
* Fixed some PHP 8.2 bugs

= 3.8.1 =
* Bugfix: MySQL 8.0 compatibility

= 3.8.0 =
* Support for Friendly Captcha Plugin
* Show number of current signups in form config
* Bugfix: Suppress some PHP Notices

= 3.7.3 =
* Support for CAPTCHA 4WP (CAPTCHA 4WP v7+ requires premium version to work)

= 3.7.2 =
* Bugfix: Wordpress 6.0 and PHP 8 compatibility

= 3.7.1 =
* Bugfix: required fields were not always checked correctly

= 3.7.0 =
* Memberlists: Saved searches can now be used for memberlists
* Bugfix: Do not send file/image data in confirmation emails
* Bugfix: Changing fields of a memberlist was not possible with newer WordPress versions
* Improved translatability: forms can now be translated with tools like WPML (use "Look for strings while pages are rendered" setting).

= 3.6.0 =
* Memberlists: Add possibility to show images in lists
* Forms: Adding support for Antispam Captchas. You'll need the the advanced-nocaptcha-recaptcha plugin to use this.
* Forms: Allow selecting the visible enum and multienum options
* Forms: Allow multiple notification e-mail addresses (separated by comma)
* Forms: Automatically convert urls to clickable links in field descriptions
* Bugfix: Fix a form error with numeric fields
* Starting with this release, only PHP >= 5.6 is tested and supported

= 3.5.0 =
* WordPress version 4.6 or greater is now required to be able to translate the plugin
* Performance improvements: fetch multiple objects at once from the Webling API
* Forms: show readonly hint in member group dropdown

= 3.4.1 =
* Updated Compatibility for WordPress 5.0
* Updated PHP Library

= 3.4.0 =
* Forms: Image and file fields are now supported
* Forms: Max number of signups can be configured
* Forms: Disable submit button after the form was submitted, to prevent multiple entries to be created
* Fix incompatibility with W3 Total Cache
* Adding "Clear Cache" button to admin page
* Remove whitespaces from the Apikey before saving to prevent copy&paste errors
* Update subscription requirements (all paid subscriptions can now use the API)

= 3.3.0 =
* Show warning if not all required fields are filled (for older browsers that do not support the html tag „required“)
* Case-insensitive sort for member lists
* Some changes for an upcoming Webling API update
* Bugfix: custom class names for form fields are now working

= 3.2.1 =
* Bugfix: caching issue

= 3.2.0 =
* Added the possibility to use a custom HTML design for memberlists
* Bugfix: Dates before 1970 were not showing in memberlist
* Display version info in settings page

= 3.1.2 =
* Bugfix: Fixes for MySQL 5.5 compatibility

= 3.1.1 =
* Bugfix: Adding new forms did not work

= 3.1.0 =
* Added an option to send confirmation emails to visitors when submitting a form

= 3.0.3 =
* Fixing a problem with the order of form fields

= 3.0.2 =
* Small bugfixes

= 3.0.1 =
* Bugfix: Not all shortcodes were converted during upgrade

= 3.0 =
* Complete rewrite
* New: Forms - Add forms which automaticly add new members to your webling database
* Shortcode for forms added: [webling_form id="2"]
* Updated shortcode format: [webling_memberlist groups="123,567"] is no longer supported. New format is: [webling_memberlist id="1"], existing shortcode will be converted during upgrade.
* Webling data is beeing cached
* Totally new admin interface, fields and groups can be configured individually for each list
* Admin menu entry for Webling

= 2.0 =
* Official release on the WordPress plugin directory

= 1.1 =
* Groups Attribute added to shortcode, you can now use [webling_memberlist groups="123,567"] to filter by group ids
* Settings link on Plugin Page

= 1.0 =
* Initial Release
