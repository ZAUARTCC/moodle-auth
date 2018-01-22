<?php
/**
 * Login integration with the rest of the Vatsim Chicago Web Infrustructure
 *
 * @package zauartcc_moodle_auth
 * @author Aaron Osher <aaron@aaronosher.io>
 * @license https://www.mozilla.org/en-US/MPL/2.0/ Mozilla Public License Version 2.0
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/authlib.php');
require_once($CFG->dirroot . '/auth/zauartcc/ZauAuth.class.php');

/**
 * Plugin for no authentication.
 */
class auth_plugin_zauartcc extends auth_plugin_base {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->pluginname = 'ZAU Auth v1';
        $this->roleauth = 'zauartcc';
        $this->authtype = 'zauartcc';
        $this->config = get_config('auth_none');
    }

    /**
     * Old syntax of class constructor. Deprecated in PHP7.
     *
     * @deprecated since Moodle 3.1
     */
    public function zau_auth_v1() {
        debugging('Use of class name as constructor is deprecated', DEBUG_DEVELOPER);
        self::__construct();
    }

    /**
     * Returns true if the username and password work or don't exist and false
     * if the user exists and the password is wrong.
     *
     * @param string $username The username
     * @param string $password The password
     * @return bool Authentication success or failure.
     */
    function user_login ($username, $password) {
        global $CFG, $DB;

        // Retrieve User with username
        $user = $DB->get_record('user', array('username'=>$username,
            'mnethostid'=>$CFG->mnet_localhost_id));
        
        if (!empty($user)) {
            return true;
        }
        return false;
    }

    /**
     * Prevent authenticate_user_login() to update the password in the DB
     * @return boolean
     */
    function prevent_local_passwords() {
        return true;
    }

    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * @return bool
     */
    function can_change_password() {
        return false;
    }
    
    /**
     * Returns true if this authentication plugin is 'internal'.
     *
     * @return bool
     */
    function is_internal() {
        return false;
    }

    function loginpage_hook() {
        global $CFG, $SESSION, $DB, $USER;

        require_once($CFG->dirroot . '/auth/zauartcc/config.php');

        $ZAU = new ZauAuth($zauAuth['base'], $zauAuth['secretkey']);

        // Check if user coming back from the login screen
        if(isset($_GET['verifier']) && isset($_GET['cid']) && isset($_GET['arg3'])) {
            if($ZAU->verifyRequest($_GET['verifier'], $_GET['cid'], $_GET['arg3'])) {
                if($ZAU->getUser()) {
                    $username = $ZAU->user['cid'];
                    $useremail = $ZAU->user['email'];
                    // find the user in the current database, by CID, not email
                    $user = $DB->get_record('user', array('username' => $username, 'deleted' => 0, 'mnethostid' => $CFG->mnet_localhost_id));

                    if (empty($user)) {
                        // deny login if setting "Prevent account creation when authenticating" is on
                        if($CFG->authpreventaccountcreation) throw new moodle_exception("noaccountyet", "auth_vatsim");
                        //retrieve more information from the provider
                        $newuser = new stdClass();
                        $newuser->email = $useremail;
                        
                        // Split name
                        $name = explode(" ", $ZAU->user['name']);
                        $firstname = $name[0];
                        $lastname = max($name);

                        $newuser->firstname =  $firstname;
                        $newuser->lastname =  $lastname;

                        create_user_record($username, '', 'vatsim');
                    } else {
                        $username = $user->username;
                    }

                    add_to_log(SITEID, 'auth_zauartcc', '', '', $username . '/' . $useremail);

                    $user = authenticate_user_login($username, null);
                    if ($user) {
                        //prefill more user information if new user
                        if (!empty($newuser)) {
                            $newuser->id = $user->id;
                            $DB->update_record('user', $newuser);
                            $user = (object) array_merge((array) $user, (array) $newuser);
                        }
                        complete_user_login($user);
                        // Redirection
                        if (user_not_fully_set_up($USER)) {
                            $urltogo = $CFG->wwwroot.'/user/edit.php';
                            // We don't delete $SESSION->wantsurl yet, so we get there later
                        } else if (isset($SESSION->wantsurl) and (strpos($SESSION->wantsurl, $CFG->wwwroot) === 0)) {
                            $urltogo = $SESSION->wantsurl;    // Because it's an address in this site
                            unset($SESSION->wantsurl);
                        } else {
                            // No wantsurl stored or external - go to homepage
                            $urltogo = $CFG->wwwroot.'/';
                            unset($SESSION->wantsurl);
                        }
                        redirect($urltogo);
                    }
                } else {
                    throw new moodle_exception("An error occurred with the login process", 'auth_zauartcc');
                }
            }
        }

        $ZAU->sendToZAU();
    }

   /**
     * Called when the user record is updated.
     *
     * We check there is no hack-attempt by a user to change his/her email address
     *
     * @param mixed $olduser     Userobject before modifications    (without system magic quotes)
     * @param mixed $newuser     Userobject new modified userobject (without system magic quotes)
     * @return boolean result
     *
     */
    function user_update($olduser, $newuser) {
        if ($olduser->email != $newuser->email) {
            return false;
        } else {
            return true;
        }
    }

}


