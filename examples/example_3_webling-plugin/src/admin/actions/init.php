<?php

function webling_admin_init()
{
	register_setting('webling-options-group', 'webling-options');

	// register cache state option
	register_setting('webling-cache', 'webling-cache-state');

	add_filter('pre_update_option_webling-options', 'webling_update_field_webling_options', 10, 2);

}
