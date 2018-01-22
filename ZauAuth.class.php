<?php
/**
 * Login integration with the rest of the Vatsim Chicago Web Infrustructure
 *
 * @package zauartcc_moodle_auth
 * @author Aaron Osher <aaron@aaronosher.io>
 * @license https://www.mozilla.org/en-US/MPL/2.0/ Mozilla Public License Version 2.0
 */

defined('MOODLE_INTERNAL') || die();

class ZauAuth
{

    private $base;

    private $secret;

    public $cid;

    public $expiry;

    public $verifier;

    public $user;

    public $error;

    /**
     * Creates instance of ZauAuth
     * 
     * Store base and secret
     */
    public function __construct($base, $secret) {
        $this->base = $base;
        $this->secret = $secret;
    }

    /**
     * Verfiies login request
     * 
     * Checks CID expiry and verifier against secret
     * @param string $verifier
     * @param int $cid
     * @param int $expiry
     * @return bool
     */
    public function verifyRequest($verifier, $cid, $expiry) {
        if (hash('sha256', $cid.$this->secret.$expiry) != $verifier || $expiry < time()) {
            return false;
        } else {
            $this->cid = $cid;
            $this->expiry = $expiry;
            $this->verifier = $verifier;
            return true;
        }
        return false;
    }

    public function getUser() {
        if($this->cid === 'admin') {
            $this->setAdminData();
            return true;
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->base . "?verifier=" . $this->verifier . "&cid=" . $this->cid . "&exp=" . $this->expiry,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        $decoded = json_decode($response, true);
        if(!$decoded['err']) {
            $this->user = $decoded['msg'];
            return true;
        } else {
            $this->error = $decoded['msg'];
            return false;
        }
    }

    public function setAdminData() {
        $this->user = [
            'cid' => 'admin',
            'email' => 'webmaster@zauartc.org'
        ];
    }

    public function sendToZAU() {
        header("Location: https://login.zauartcc.org?action=moodle");
        die();
    }
}