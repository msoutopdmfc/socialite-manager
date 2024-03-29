<?php

namespace Msoutopdmfc\Manager\Contracts\OAuth1;

use Msoutopdmfc\Manager\Contracts\ConfigInterface as Config;

interface ProviderInterface
{
    /**
     * @param \Msoutopdmfc\Manager\Contracts\ConfigInterface $config
     *
     * @return $this
     */
    public function setConfig(Config $config);
}
