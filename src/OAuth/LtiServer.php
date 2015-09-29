<?php

namespace Franzl\Lti\OAuth;

use League\OAuth1\Client\Credentials\TokenCredentials;
use League\OAuth1\Client\Server\Server;
use League\OAuth1\Client\Server\User;

class LtiServer extends Server
{
    /**
     * Get the URL for retrieving temporary credentials.
     *
     * @return string
     */
    public function urlTemporaryCredentials()
    {
        return '';
    }

    /**
     * Get the URL for redirecting the resource owner to authorize the client.
     *
     * @return string
     */
    public function urlAuthorization()
    {
        return '';
    }

    /**
     * Get the URL retrieving token credentials.
     *
     * @return string
     */
    public function urlTokenCredentials()
    {
        return '';
    }

    /**
     * Get the URL for retrieving user details.
     *
     * @return string
     */
    public function urlUserDetails()
    {
        return '';
    }

    /**
     * Take the decoded data from the user details URL and convert
     * it to a User object.
     *
     * @param mixed $data
     * @param TokenCredentials $tokenCredentials
     *
     * @return User
     */
    public function userDetails($data, TokenCredentials $tokenCredentials)
    {
        return null;
    }

    /**
     * Take the decoded data from the user details URL and extract
     * the user's UID.
     *
     * @param mixed $data
     * @param TokenCredentials $tokenCredentials
     *
     * @return string|int
     */
    public function userUid($data, TokenCredentials $tokenCredentials)
    {
        return '';
    }

    /**
     * Take the decoded data from the user details URL and extract
     * the user's email.
     *
     * @param mixed $data
     * @param TokenCredentials $tokenCredentials
     *
     * @return string
     */
    public function userEmail($data, TokenCredentials $tokenCredentials)
    {
        return '';
    }

    /**
     * Take the decoded data from the user details URL and extract
     * the user's screen name.
     *
     * @param mixed $data
     * @param TokenCredentials $tokenCredentials
     *
     * @return string
     */
    public function userScreenName($data, TokenCredentials $tokenCredentials)
    {
        return '';
    }
}
