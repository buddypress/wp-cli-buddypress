#!/bin/bash

set -ex

# Run the functional tests
BEHAT_TAGS=$(php utils/behat-tags.php)
vendor/behat/behat/bin/behat --format progress $BEHAT_TAGS --strict
