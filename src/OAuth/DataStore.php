<?php

namespace Franzl\Lti\OAuth;

use Franzl\Lti\ConsumerNonce;
use Franzl\Lti\ToolProvider;

/**
 * Class to represent an OAuth datastore
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @version 2.5.00
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class DataStore
{
    /**
     * @var ToolProvider Tool Provider object.
     */
    private $tool_provider = null;

    /**
     * Class constructor.
     *
     * @param ToolProvider $tool_provider Tool_Provider object
     */
    public function __construct($tool_provider)
    {
        $this->tool_provider = $tool_provider;
    }

    /**
     * Create an Consumer object for the tool consumer.
     *
     * @param string $consumer_key Consumer key value
     *
     * @return Consumer Consumer object
     */
    public function lookupConsumer($consumer_key)
    {
        return new Consumer(
            $this->tool_provider->consumer->getKey(),
            $this->tool_provider->consumer->secret
        );
    }

    /**
     * Create an Token object for the tool consumer.
     *
     * @param string $consumer   Consumer object
     * @param string $token_type Token type
     * @param string $token      Token value
     *
     * @return Token Token object
     */
    public function lookupToken($consumer, $token_type, $token)
    {
        return new Token($consumer, '');
    }

    /**
     * Lookup nonce value for the tool consumer.
     *
     * @param Consumer $consumer  Consumer object
     * @param string        $token     Token value
     * @param string        $value     Nonce value
     * @param string        $timestamp Date/time of request
     *
     * @return boolean True if the nonce value already exists
     */
    public function lookupNonce($consumer, $token, $value, $timestamp)
    {
        $nonce = new ConsumerNonce($this->tool_provider->consumer, $value);
        $ok = !$nonce->load();
        if ($ok) {
            $ok = $nonce->save();
        }
        if (!$ok) {
            $this->tool_provider->reason = 'Invalid nonce.';
        }

        return !$ok;
    }

    /**
     * Get new request token.
     *
     * @param Consumer $consumer  Consumer object
     * @param string        $callback  Callback URL
     *
     * @return string Null value
     */
    public function newRequestToken($consumer, $callback = null)
    {
        return null;
    }

    /**
     * Get new access token.
     *
     * @param string        $token     Token value
     * @param Consumer $consumer  Consumer object
     * @param string        $verifier  Verification code
     *
     * @return string Null value
     */
    public function newAccessToken($token, $consumer, $verifier = null)
    {
        return null;
    }
}
