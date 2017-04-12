# wp-cli-buddypress

WP-CLI commands for use with BuddyPress. Currently supported commands:

* `bp core activate` -- Activate a component.
* `bp core deactivate` -- Deactivate a component.
* `bp activity create` -- Create a single activity item.
* `bp activity generate` -- Generate a large number of random activity items.
* `bp group add_member` -- Add a member to a BuddyPress group.
* `bp group create` -- Create new BuddyPress groups.
* `bp group update` -- Update an existing BuddyPress group.
* `bp member generate` -- Create lots of site members, with the proper BP metadata.
* `bp xprofile create_group` -- Create an XProfile field group.
* `bp xprofile list_fields` -- List XProfile fields.
* `bp xprofile create_field` -- Create an XProfile field.
* `bp xprofile delete_field` -- Create an XProfile field.
* `bp xprofile set_data` -- Set XProfile data for a specific user/field combination.

## Why doesn't this do _x_?

Because I haven't built it yet. I'm filling in commands as I need them, which means that they are largely developer-focused. I'll fill in more commands as I need them. Pull requests will be enthusiastically received.

## System Requirements

* PHP >=5.3
* wp-cli >=0.15.0

If you need support for wp-cli < 0.15.0, please use the 1.1.x branch.

## Setup

* Install [wp-cli](https://wp-cli.org)
* Install wp-cli-buddypress. Manuall installation is recommended, though Composer installation should work too. See https://github.com/wp-cli/wp-cli/wiki/Community-Packages for information.
* Inside of a WP installation, type `wp bp`. You should see a list of available commands.

## Changelog

### 1.4.0

* New commands: `bp xprofile list_fields`, `bp xprofile delete_field`
* Added the ability to pass multiple comma-separated values when updating xprofile fields
* Fixed bug in component activation

### 1.3.1

* Improved logic for user-id parsing

### 1.3.0

* New commands: `bp group get_members`, `bp group update`
* Ability to pass 'content' when using `bp activity generate`
* When using `bp activity generate` with type=activity_update and component=groups, format the activity action properly

### 1.2.0

* Use wp-cli's new fourth-level commands
* New commands: xprofile create_group, xprofile create_field, xprofile set_data

### 1.1.1

* Ensure that components have their install routine run after activation

### 1.1

* New commands: activate, deactivate, activity_create, activity_generate
* Improved documentation
* Added support for installation via Composer

### 1.0

* Initial release
