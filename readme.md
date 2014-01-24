# wp-cli-buddypress

WP-CLI commands for use with BuddyPress. Currently supported commands:

* `bp activate` -- Activate a component.
* `bp deactivate` -- Deactivate a component.
* `bp group_create` -- Create new BuddyPress groups.
* `bp group_add_member` -- Add a member to a BuddyPress group.
* `bp member_generate` -- Create lots of site members, with the proper BP metadata.

## Why doesn't this do _x_?

Because I haven't built it yet. I'm filling in commands as I need them, which means that they are largely developer-focused. I'll fill in more commands as I need them. Pull requests will be enthusiastically received.

## System Requirements

* PHP >=5.3
* Composer
* wp-cli

## Setup

* Install [wp-cli](https://wp-cli.org)
* Install wp-cli-buddypress. I hope to add the package as an official Community Package soon (for installation via Composer), but for now you can install manually. See https://github.com/wp-cli/wp-cli/wiki/Community-Packages#installing-a-package-without-composer for more details.

## Changelog

### 1.0

* Initial release
