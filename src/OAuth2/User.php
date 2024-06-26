<?php

namespace Msoutopdmfc\Manager\OAuth2;

use Msoutopdmfc\Socialite\Two\User as BaseUser;

class User extends BaseUser
{
    /**
     * The User Credentials.
     *
     * e.g. access_token, refresh_token, etc.
     *
     * @var array
     */
    public $accessTokenResponseBody;

    /**
     * Set the credentials on the user.
     *
     * Might include things such as the token and refresh token
     *
     * @param array $accessTokenResponseBody
     *
     * @return $this
     */
    public function setAccessTokenResponseBody(array $accessTokenResponseBody)
    {
        $this->accessTokenResponseBody = $accessTokenResponseBody;

        return $this;
    }
}
