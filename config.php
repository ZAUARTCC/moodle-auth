<?php
/**
 * Login integration with the rest of the Vatsim Chicago Web Infrustructure
 *
 * @package zauartcc_moodle_auth
 * @author Aaron Osher <aaron@aaronosher.io>
 * @license https://www.mozilla.org/en-US/MPL/2.0/ Mozilla Public License Version 2.0
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Contains configuration paramaters for the plugin
 */
$zauAuth = array();

/**
 * Location of ZAUARTCC Login Interface
 */
$zauAuth['base'] = 'https://login.zauartcc.org/moodle.php';

/**
 * Secret key for verifying login attempts
 */
$zauAuth['secretkey'] = getenv('ZAU_AUTH_SECRET_KEY');