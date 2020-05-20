<?php

// Bail if WP-CLI is not present.
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$wpcli_entity_autoloader = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( file_exists( $wpcli_entity_autoloader ) ) {
	require_once $wpcli_entity_autoloader;
}

WP_CLI::add_command(
	'bp',
	'BuddyPressCommand',
	array( 'before_invoke' => 'BuddyPressCommand::check_dependencies' )
);

WP_CLI::add_command(
	'bp signup',
	'BP_Signup_Command',
	array( 'before_invoke' => 'BP_Signup_Command::check_dependencies' )
);

WP_CLI::add_command(
	'bp tool',
	'BP_Tool_Command',
	array( 'before_invoke' => 'BP_Tool_Command::check_dependencies' )
);

WP_CLI::add_command(
	'bp notification',
	'BP_Notification_Command',
	array( 'before_invoke' => 'BP_Notification_Command::check_dependencies' )
);

WP_CLI::add_command(
	'bp email',
	'BP_Email_Command',
	array( 'before_invoke' => 'BP_Email_Command::check_dependencies' )
);

WP_CLI::add_command(
	'bp member',
	'BP_Member_Command',
	array( 'before_invoke' => 'BP_Member_Command::check_dependencies' )
);

WP_CLI::add_command(
	'bp message',
	'BP_Message_Command',
	array( 'before_invoke' => 'BP_Message_Command::check_dependencies' )
);

WP_CLI::add_command(
	'bp component',
	'BP_Components_Command',
	array( 'before_invoke' => 'BP_Components_Command::check_dependencies' )
);

WP_CLI::add_command(
	'bp friend',
	'BP_Friends_Command',
	array( 'before_invoke' => 'BP_Friends_Command::check_dependencies' )
);

WP_CLI::add_command(
	'bp activity',
	'BP_Activity_Command',
	array( 'before_invoke' => 'BP_Activity_Command::check_dependencies' )
);

WP_CLI::add_command(
	'bp activity favorite',
	'BP_Activity_Favorite_Command',
	array( 'before_invoke' => 'BP_Activity_Command::check_dependencies' )
);

WP_CLI::add_command(
	'bp group',
	'BP_Group_Command',
	array( 'before_invoke' => 'BP_Group_Command::check_dependencies' )
);

WP_CLI::add_command(
	'bp group member',
	'BP_Group_Member_Command',
	array( 'before_invoke' => 'BP_Group_Command::check_dependencies' )
);

WP_CLI::add_command(
	'bp group invite',
	'BP_Group_Invite_Command',
	array( 'before_invoke' => 'BP_Group_Command::check_dependencies' )
);

WP_CLI::add_command(
	'bp xprofile',
	'BP_XProfile_Command',
	array( 'before_invoke' => 'BP_XProfile_Command::check_dependencies' )
);

WP_CLI::add_command(
	'bp xprofile group',
	'BP_XProfile_Group_Command',
	array( 'before_invoke' => 'BP_XProfile_Command::check_dependencies' )
);

WP_CLI::add_command(
	'bp xprofile field',
	'BP_XProfile_Field_Command',
	array( 'before_invoke' => 'BP_XProfile_Command::check_dependencies' )
);

WP_CLI::add_command(
	'bp xprofile data',
	'BP_XProfile_Data_Command',
	array( 'before_invoke' => 'BP_XProfile_Command::check_dependencies' )
);
