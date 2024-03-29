<?php

namespace Msoutopdmfc\Manager\Contracts\Helpers;

interface ConfigRetrieverInterface
{
    /**
     * @param string $providerName
     * @param array  $additionalConfigKeys
     *
     * @return \Msoutopdmfc\Manager\Contracts\ConfigInterface
     *
     * @throws \Msoutopdmfc\Manager\Exception\MissingConfigException
     */
    public function fromServices($providerName, array $additionalConfigKeys = []);
}
