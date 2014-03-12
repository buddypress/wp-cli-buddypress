# wp-cli-buddypress

WP-CLI commands for use with BuddyPress. Currently supported commands:

* `bp core activate` -- Activate a component.
* `bp core deactivate` -- Deactivate a component.
* `bp activity create` -- Create a single activity item.
* `bp activity generate` -- Generate a large number of random activity items.
* `bp group create` -- Create new BuddyPress groups.
* `bp group add_member` -- Add a member to a BuddyPress group.
* `bp member generate` -- Create lots of site members, with the proper BP metadata.
* `bp xprofile create_group` -- Create an XProfile field group.

## Why doesn't this do _x_?

Because I haven't built it yet. I'm filling in commands as I need them, which means that they are largely developer-focused. I'll fill in more commands as I need them. Pull requests will be enthusiastically received.

## System Requirements

* PHP >=5.3
* Composer
* wp-cli >=0.15.0

If you need support for wp-cli < 0.15.0, please use the 1.1.x branch.

## Setup

* Install [wp-cli](https://wp-cli.org)
* Install wp-cli-buddypress. See https://github.com/wp-cli/wp-cli/wiki/Community-Packages for information on installing via Composer or manually.

## Changelog

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
