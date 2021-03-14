# Changelog

## 2.0.1

* Load the `bp scaffold` command only if the Scaffold package is present
* Updated PHPCS ruleset and fixed phpcs rules
* Fix changelog.md markdown bugs
* Test against the PHP 8.0 version

## 2.0.0

* Abstracted activity ID fetching to the `Activity_Fetcher` helper class
* The package was upgraded to follow WP-CLI best practices in code organization and structure
* The `before_invoke` callable was abstracted into their component class
* We made sure all Behat tests were passing correctly
* We fixed several minor bugs in several commands
* We are making the use of `wp-cli/wp-cli-tests` for all tests (phpcs, behat, etc).
* Improved .travis.yml config
* Removed PHP 5.4 support from Travis
* Support to PHP 5.6+ added
* Improved the readme documentation
* Updated to use the more up to date `WP_CLI::log()` instead of `WP_CLI::line()`
* Forced the creation of the signups table when using the `wp bp signup` command and the tabled wasn't present.
* Return proper success/error messages when using `parent::_delete` or `parent::_update`
* Improved the commands PHPDocs, very useful when using the `help`
* Updated to fetch and check values from PHPDoc instead of checking in PHP
* Updated or removed the `default` values from several commands (most of them were wrong)
* New commands:
  * `wp bp group meta` - Used to manage Group Meta (custom fields).
  * `wp bp activity meta` - Used to manage Activity Meta (custom fields).
  * `wp bp tool signup` - Used to (de)activate the Signup feature.
  * `wp bp scaffold tests` - Used to scaffold BuddyPress specific testing code for plugins.

## 1.8.0

* `wp-cli-buddypress` requires PHP 5.4
* `bp notification` commands introduced

## 1.7.0

* Updated `bp` and `bp xprofile` commands PHPDoc info
* Fixed `component list` commands output
* Check if the `component` exists first before using it
* Fixed `component` Behat tests
* Removed PHP 5.3 support from Travis

## 1.6.0

* `bp email` commands introduced
* With PSR-4 support for the classes

## 1.5.0

* CRUD commands introduced to the main BuddyPress components
* Behat tests added for all commands
* Codebase fixed for WPCS

## 1.4.0

* New commands: `bp xprofile list_fields`, `bp xprofile delete_field`
* Added the ability to pass multiple comma-separated values when updating xprofile fields
* Fixed bug in component activation

## 1.3.1

* Improved logic for user-id parsing

## 1.3.0

* New commands: `bp group get_members`, `bp group update`
* Ability to pass 'content' when using `bp activity generate`
* When using `bp activity generate` with type=activity_update and component=groups, format the activity action properly

## 1.2.0

* Use wp-cli's new fourth-level commands
* New commands: xprofile create_group, xprofile create_field, xprofile set_data

## 1.1.1

* Ensure that components have their install routine run after activation

## 1.1

* New commands: activate, deactivate, activity_create, activity_generate
* Improved documentation
* Added support for installation via Composer

## 1.0

* Initial release
