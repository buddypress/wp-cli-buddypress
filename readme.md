buddypress/wp-cli-buddypress
===============================

Manage BuddyPress through the command-line.

[![Build Status](https://travis-ci.org/buddypress/wp-cli-buddypress.svg?branch=master)](https://travis-ci.org/buddypress/wp-cli-buddypress)

Quick links: [Installing](#installing) | [Support](#support)

## Installing

To install the latest version of this package, run:

    wp package install git@github.com:buddypress/wp-cli-buddypress.git

## Using

This package implements several commands, depending on the current activated BuddyPress component:

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

## Support

Github issues aren't for general support questions, but there are other venues you can try: https://buddypress.org/support
