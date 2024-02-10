# Changelog

This library adheres to [Semantic Versioning](https://semver.org/) and [Keep a CHANGELOG](https://keepachangelog.com/en/1.0.0/).

## Unreleased

## Added

* New commands:
  * `wp bp notice` - Used to manage Sitewide notices.
  * `wp bp tool reinstall` - Alias of the `wp bp email reinstall` command, we will deprecate the latter in the future.

### Changed

* Prefer short array syntax (This is different from WCS supports)
* Composer: packages upgraded
* Misc linting updates
* Github Action: Testing against PHP 8.3
* Updated deprecated function from `bp_get_group_permalink` into `bp_get_group_url`
* Activity: make tests more deterministic
* phpDoc
  * All `delete` commands' output were standardized
	* All `delete` commands' alias were standardized
	* Invalid `format` option `haml` removed
* Confirmation message updated for `delete` commands that accepts multiple values
* `wp bp group invite remove` updated to `wp bp group invite uninvite` to avoid conflict with the delete/remove command

## 2.0.2

* Github Actions: upgraded actions
* Github Actions: decreased the number of legacy PHP versions to test against
* Github Actions: Testing against the PHP 8.2 versions
* Behat: fix a small bug
* Updated `composer.json` packages
* Removed hardcoded `composer.lock` file (let the PHP version decide which packages to install)

## 2.0.1

* Load the `bp scaffold` command only if the composer Scaffold package is installed
* Updated PHPCS ruleset and fixed phpcs rules
* Fix changelog.md markdown bugs
* Moved from Travis to Github Actions for CI tests
* CI tests against PHP 8.0 now
* Upgraded the wp-cli/wp-cli-tests package to latest
* Upgraded the wp-cli/wp-cli package to latest
* Added a new behat.yml file for the test setup
* wp bp component:
  * properly listing active components.
  * status string updated from uppercase to lowercase.
  * we are checking the component status correctly.
  * we improved the behat tests.
  * we are decidubg the component description.
  * we are escaping the component title.

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
