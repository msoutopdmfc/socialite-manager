<?php

namespace Msoutopdmfc\Manager\Contracts\OAuth2;

use Msoutopdmfc\Socialite\Two\ProviderInterface as SocialiteOauth2ProviderInterface;
use Msoutopdmfc\Manager\Contracts\ConfigInterface as Config;

interface ProviderInterface extends SocialiteOauth2ProviderInterface
{
    /**
     * @param \Msoutopdmfc\Manager\Contracts\ConfigInterface $config
     *
     * @return $this
     */
    public function setConfig(Config $config);
}
