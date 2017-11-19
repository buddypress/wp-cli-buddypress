# wp-cli-buddypress

WP-CLI commands for use with BuddyPress. Currently supported commands:

### Core Commands
* `bp core activate` -- Activate a component.
* `bp core deactivate` -- Deactivate a component.

### Tool Commands
* `bp tool repair` -- Repair something on BuddyPress.
* `bp tool reinstall_emails` -- Reinstall BuddyPress default emails.

### Signup Commands
* `bp signup add` -- Create a signup.
* `bp signup delete` -- Delete a signup.
* `bp signup get` -- Get a signup.
* `bp signup activate` -- Activate a signup.
* `bp signup generate` -- Generate signups.
* `bp signup resend` -- Resend a signup.
* `bp signup list` -- Get a list of signups.

### Activity Commands
* `bp activity create` -- Create a single activity item.
* `bp activity delete` -- Delete an activity.
* `bp activity generate` -- Generate a large number of random activity items.
* `bp activity list` -- Get a list of activities.
* `bp activity get` -- Get an activity.
* `bp activity spam` -- Spam an activity.
* `bp activity ham` -- Ham an activity.
* `bp activity post_update` -- Post an activity update.
* `bp activity comment` -- Add an activity comment.
* `bp activity delete_comment` -- Delete an activity comment.
* `bp activity favorite add` -- Add an activity item as a favorite for a user.
* `bp activity favorite add` -- Remove an activity item as a favorite for a user.
* `bp activity favorite items` -- Get a user's favorite activity items.

### Group Commands
* `bp group create` -- Create a group.
* `bp group delete` -- Delete a group.
* `bp group get` -- Get a group.
* `bp group update` -- Update a group.
* `bp group list` -- List groups.
* `bp group generate` -- Generate a large number of random groups.
* `bp group invite add` -- Invite a user from a group.
* `bp group invite remove` -- Uninvite a user from a group.
* `bp group invite list` -- Get a list of invitations from a group.
* `bp group invite generate` -- Generate random group invitations.
* `bp group invite accept` -- Accept a group invitation.
* `bp group invite reject` -- Reject a group invitation.
* `bp group invite delete` -- Delete a group invitation.
* `bp group member add` -- Add a group member.
* `bp group member remove` -- Remove a group member.
* `bp group member list` -- List group members.
* `bp group member promote` -- Promote a member to a new status withing a group.
* `bp group member demote` -- Demote user to the 'member' status.
* `bp group member ban` -- Ban a member from a group.
* `bp group member unban` -- Unban a member from a group.

### Member Command
* `bp member generate` -- Create lots of site members, with the proper BP metadata.

### XProfile Commands
* `bp xprofile group create` -- Create an XProfile field group.
* `bp xprofile group delete` -- Delete an XProfile field group.
* `bp xprofile group get` -- Fetch an XProfile field group.
* `bp xprofile field create` -- Create an XProfile field.
* `bp xprofile field delete` -- Delete an XProfile field.
* `bp xprofile field get` -- Get an XProfile field.
* `bp xprofile list` -- List XProfile fields.
* `bp xprofile data set` -- Set XProfile data for user.
* `bp xprofile data get` -- Get XProfile data for user.
* `bp xprofile data delete` -- Delete XProfile data for user.

## Why doesn't this do _x_?

Because I haven't built it yet. I'm filling in commands as I need them, which means that they are largely developer-focused. I'll fill in more commands as I need them. Pull requests will be enthusiastically received.

## System Requirements

* PHP >=5.3
* wp-cli >=0.23.0

If you need support for wp-cli < 0.15.0, please use the 1.1.x branch.

## Setup

* Install [wp-cli](https://wp-cli.org)
* Install wp-cli-buddypress. Manuall installation is recommended, though Composer installation should work too. See https://github.com/wp-cli/wp-cli/wiki/Community-Packages for information.
* Inside of a WP installation, type `wp bp`. You should see a list of available commands.

## Changelog

### 1.5.0

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
