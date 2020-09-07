buddypress/wp-cli-buddypress
===============================

Manage BuddyPress through the command-line.

[![Build Status](https://travis-ci.org/buddypress/wp-cli-buddypress.svg?branch=master)](https://travis-ci.org/buddypress/wp-cli-buddypress)

Quick links: [Installing](#installing) | [Support](#support)

## Installing

The `wp-cli-buddypress` comes installed by default with BuddyPress. So if you need to use the latest version, run:

~~~
wp package install git@github.com:buddypress/wp-cli-buddypress.git
~~~

In many cases the default memory limit will not be enough to run composer so running the following instead is generally recommended:

~~~
php -d memory_limit=512M "$(which wp)" package install git@github.com:buddypress/wp-cli-buddypress.git
~~~

## Using

This package adds commands to all core BuddyPress components. The component used **needs** to be activated for it to be used. Here are a few examples:

### wp bp

Manage all BuddyPress commands.

~~~
wp bp
~~~

### wp bp activity

Manage BuddyPress Activities.

~~~
wp bp activity
~~~

**EXAMPLES**

    # Create Activity
	$ wp bp activity create
    Success: Successfully created new activity item (ID #5464).

    # Create Group Activity
    $ wp bp activity add --component=groups --item-id=2 --user-id=10
    Success: Successfully created new activity item (ID #48949)

### wp bp group

Manage BuddyPress Groups.

~~~
wp bp group
~~~

**EXAMPLES**

	# Create Group
	$ wp bp group create --name="Totally Cool Group"
	Success: Group (ID 5465) created: http://example.com/groups/totally-cool-group/

	# Delete a Group
	$ wp bp group delete group-slug --yes
	Success: Group successfully deleted.

### wp bp messages

Manage BuddyPress Messages.

~~~
wp bp messages
~~~

**EXAMPLES**

	# Create message
    wp bp message create --from=user1 --to=user2 --subject="Message Title" --content="We are ready"
    Success: Message successfully created.

	# Delete thread
    $ wp bp message delete-thread 456456 --user-id=user_logon --yes
    Success: Thread successfully deleted.

### wp bp notification

Manage BuddyPress XProfile.

~~~
wp bp xprofile
~~~

**EXAMPLES**

	# Create a xprofile group.
    $ wp bp xprofile group create --name="Group Name" --description="Xprofile Group Description"
    Success: Created XProfile field group "Group Name" (ID 123).

	# List xprofile fields.
    $ wp bp xprofile field list

	# Save a xprofile data to a user with its field and value.
    $ wp bp xprofile data set --user-id=45 --field-id=120 --value=teste
    Success: Updated XProfile field "Field Name" (ID 120) with value  "teste" for user user_login (ID 45).

### wp bp notification

Manage BuddyPress Notifications.

~~~
wp bp notification
~~~

**EXAMPLES**

    # Create notification item.
    $ wp bp notification create
    Success: Successfully created new notification. (ID #5464)

    # Delete a notification item.
    $ wp bp notification delete 520
    Success: Notification deleted.

### wp bp email

Manage BuddyPress Emails

~~~
wp bp email
~~~

**EXAMPLES**

   	# Create email
	$ wp bp email create --type=new-event --type-description="Send an email when a new event is created" --subject="[{{{site.name}}}] A new event was created" --content="<a href='{{{some.custom-token-url}}}'></a>A new event</a> was created" --plain-text-content="A new event was created"
 	Success: Email post created for type "new-event".

    # Create email with content from given file
    $ wp bp email create ./email-content.txt --type=new-event --type-description="Send an email when a new event is created" --subject="[{{{site.name}}}] A new event was created" --plain-text-content="A new event was created"
    Success: Email post created for type "new-event".

### wp bp member

Manage BuddyPress Members.

~~~
wp bp member
~~~

**EXAMPLES**

    # Generate BuddyPress members.
    $ wp bp member generate

### wp bp signup

Manage BuddyPress Signups

~~~
wp bp signup
~~~

**EXAMPLES**

    # Create a signup
	$ wp bp signup create --user-login=test_user --user-email=teste@site.com
    Success: Successfully added new user signup (ID #345).

	# Activate a signup
	$ wp bp signup activate ee48ec319fef3nn4
	Success: Signup activated, new user (ID #545).

### wp bp tool

Manage BuddyPress repairs tools.

~~~
wp bp tool
~~~

**EXAMPLES**

    # Repairing friend-count
	$ wp bp tool repair friend-count
    Success: Counting the number of friends for each user. Complete!

    # Activate signup
    $ wp bp tool signup 1
    Success: Signup tool updated.

## Support

Github issues aren't for general support questions, but there are other venues you can try: https://buddypress.org/support
