<?php

namespace Msoutopdmfc\Manager;

use Illuminate\Contracts\Container\Container as Application;
use Msoutopdmfc\Socialite\Contracts\Factory as SocialiteFactory;
use Msoutopdmfc\Socialite\One\AbstractProvider as SocialiteOAuth1AbstractProvider;
use Msoutopdmfc\Socialite\SocialiteManager;
use Msoutopdmfc\Socialite\Two\AbstractProvider as SocialiteOAuth2AbstractProvider;
use League\OAuth1\Client\Server\Server as OAuth1Server;
use Msoutopdmfc\Manager\Contracts\Helpers\ConfigRetrieverInterface;
use Msoutopdmfc\Manager\Exception\InvalidArgumentException;

class SocialiteWasCalled
{
    const SERVICE_CONTAINER_PREFIX = 'SocialiteProviders.config.';

    /**
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $app;

    /**
     * @var \Msoutopdmfc\Manager\Contracts\Helpers\ConfigRetrieverInterface
     */
    private $configRetriever;

    /**
     * @var array
     */
    private $spoofedConfig = [
        'client_id' => 'spoofed_client_id',
        'client_secret' => 'spoofed_client_secret',
        'redirect' => 'spoofed_redirect',
    ];

    /**
     * @param \Illuminate\Contracts\Container\Container $app
     * @param \Msoutopdmfc\Manager\Contracts\Helpers\ConfigRetrieverInterface $configRetriever
     */
    public function __construct(Application $app, ConfigRetrieverInterface $configRetriever)
    {
        $this->app = $app;
        $this->configRetriever = $configRetriever;
    }

    /**
     * @param string $providerName  'meetup'
     * @param string $providerClass 'Your\Name\Space\ClassNameProvider' must extend
     *                              either Msoutopdmfc\Socialite\Two\AbstractProvider or
     *                              Msoutopdmfc\Socialite\One\AbstractProvider
     * @param string $oauth1Server  'Your\Name\Space\ClassNameServer' must extend League\OAuth1\Client\Server\Server
     *
     * @return void
     *
     * @throws \Msoutopdmfc\Manager\Exception\InvalidArgumentException
     */
    public function extendSocialite($providerName, $providerClass, $oauth1Server = null)
    {
        /** @var SocialiteManager $socialite */
        $socialite = $this->app->make(SocialiteFactory::class);

        $this->classExists($providerClass);
        if ($this->isOAuth1($oauth1Server)) {
            $this->classExists($oauth1Server);
            $this->classExtends($providerClass, SocialiteOAuth1AbstractProvider::class);
        }

        $socialite->extend(
            $providerName,
            function () use ($socialite, $providerName, $providerClass, $oauth1Server) {
                $provider = $this->buildProvider($socialite, $providerName, $providerClass, $oauth1Server);
                if (defined('SOCIALITEPROVIDERS_STATELESS') && SOCIALITEPROVIDERS_STATELESS) {
                    return $provider->stateless();
                }

                return $provider;
            }
        );
    }

    /**
     * @param \Msoutopdmfc\Socialite\SocialiteManager $socialite
     * @param string                              $providerName
     * @param string                              $providerClass
     * @param null|string                         $oauth1Server
     *
     * @return \Msoutopdmfc\Socialite\One\AbstractProvider|\Msoutopdmfc\Socialite\Two\AbstractProvider
     *
     * @throws \Msoutopdmfc\Manager\Exception\MissingConfigException
     */
    protected function buildProvider(SocialiteManager $socialite, $providerName, $providerClass, $oauth1Server)
    {
        if ($this->isOAuth1($oauth1Server)) {
            return $this->buildOAuth1Provider($socialite, $providerClass, $providerName, $oauth1Server);
        }

        return $this->buildOAuth2Provider($socialite, $providerClass, $providerName);
    }

    /**
     * Build an OAuth 1 provider instance.
     *
     * @param \Msoutopdmfc\Socialite\SocialiteManager $socialite
     * @param string $providerClass must extend Msoutopdmfc\Socialite\One\AbstractProvider
     * @param string $providerName
     * @param string $oauth1Server  must extend League\OAuth1\Client\Server\Server
     *
     * @return \Msoutopdmfc\Socialite\One\AbstractProvider
     *
     * @throws \Msoutopdmfc\Manager\Exception\MissingConfigException
     */
    protected function buildOAuth1Provider(SocialiteManager $socialite, $providerClass, $providerName, $oauth1Server)
    {
        $this->classExtends($oauth1Server, OAuth1Server::class);

        $config = $this->getConfig($providerClass, $providerName);

        $configServer = $socialite->formatConfig($config->get());

        $provider = new $providerClass(
            $this->app->offsetGet('request'), new $oauth1Server($configServer)
        );

        $provider->setConfig($config);

        return $provider;
    }

    /**
     * Build an OAuth 2 provider instance.
     *
     * @param SocialiteManager $socialite
     * @param string           $providerClass must extend Msoutopdmfc\Socialite\Two\AbstractProvider
     * @param string           $providerName
     *
     * @return \Msoutopdmfc\Socialite\Two\AbstractProvider
     *
     * @throws \Msoutopdmfc\Manager\Exception\MissingConfigException
     */
    protected function buildOAuth2Provider(SocialiteManager $socialite, $providerClass, $providerName)
    {
        $this->classExtends($providerClass, SocialiteOAuth2AbstractProvider::class);

        $config = $this->getConfig($providerClass, $providerName);

        $provider = $socialite->buildProvider($providerClass, $config->get());

        $provider->setConfig($config);

        return $provider;
    }

    /**
     * @param string $providerClass
     * @param string $providerName
     *
     * @return \Msoutopdmfc\Manager\Contracts\ConfigInterface
     *
     * @throws \Msoutopdmfc\Manager\Exception\MissingConfigException
     */
    protected function getConfig($providerClass, $providerName)
    {
        return $this->configRetriever->fromServices(
            $providerName, $providerClass::additionalConfigKeys()
        );
    }

    /**
     * Check if a server is given, which indicates that OAuth1 is used.
     *
     * @param string $oauth1Server
     *
     * @return bool
     */
    private function isOAuth1($oauth1Server)
    {
        return !empty($oauth1Server);
    }

    /**
     * @param string $class
     * @param string $baseClass
     *
     * @return void
     *
     * @throws \Msoutopdmfc\Manager\Exception\InvalidArgumentException
     */
    private function classExtends($class, $baseClass)
    {
        if (false === is_subclass_of($class, $baseClass)) {
            throw new InvalidArgumentException("{$class} does not extend {$baseClass}");
        }
    }

    /**
     * @param string $providerClass
     *
     * @return void
     *
     * @throws \Msoutopdmfc\Manager\Exception\InvalidArgumentException
     */
    private function classExists($providerClass)
    {
        if (!class_exists($providerClass)) {
            throw new InvalidArgumentException("{$providerClass} doesn't exist");
        }
    }
}
