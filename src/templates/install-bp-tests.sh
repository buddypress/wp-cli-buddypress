#!/bin/sh

set -ex

BP_VERSION=${2-latest}
BP_DIR=/tmp/buddypress
BP_SVN=https://buddypress.svn.wordpress.org

# Set SVN paths.
if [ "$BP_VERSION" = "latest" ]; then
  BP_DIR="$BP_DIR/latest"
  BP_SVN="$BP_SVN/trunk"
else
  BP_DIR="$BP_DIR/$BP_VERSION"
  BP_SVN="$BP_SVN/tags/$BP_VERSION"
fi

# Create directory.
mkdir -p "$BP_DIR"

# Install BuddyPress and test suite.
if [ ! -d "$BP_DIR/src" ]; then
  svn co --quiet "$BP_SVN" "$BP_DIR"
fi

# Edit WordPress testing files to load BuddyPress.
grep -qF bootstrap-buddypress.php tests/bootstrap.php || echo "require 'bootstrap-buddypress.php';" >> tests/bootstrap.php
