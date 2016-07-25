<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 25/07/2016
 * Time: 20:51
 */

namespace twhiston\tg\Phar;

use Humbug\SelfUpdate\Updater;
use Humbug\SelfUpdate\VersionParser;
use Humbug\SelfUpdate\Exception\HttpRequestException;
use Humbug\SelfUpdate\Exception\InvalidArgumentException;
use Humbug\SelfUpdate\Exception\JsonParsingException;
use Humbug\SelfUpdate\Strategy\StrategyInterface;

/**
 * Because the github strategy doesnt let us access the remote version
 * AND we cant call getCurrentRemoteVersion because we dont have an Updater object
 * we cant actually get the download url, this totally sucks.
 * The only option is to duplicate the code in this class and just use the interface
 * boo-urns
 */
class BitbucketStrategy implements StrategyInterface
{

    const API_URL = 'https://packagist.org/packages/%s.json';

    const STABLE = 'stable';

    const UNSTABLE = 'unstable';

    const ANY = 'any';

    /**
     * @var string
     */
    protected $localVersion;

    /**
     * @var string
     */
    protected $remoteVersion;

    /**
     * @var string
     */
    private $remoteUrl;

    /**
     * @var string
     */
    private $pharName;

    /**
     * @var string
     */
    private $packageName;

    /**
     * @var string
     */
    private $stability = self::STABLE;

    /**
     * Download the remote Phar file.
     *
     * @param Updater $updater
     * @return void
     */
    public function download(Updater $updater)
    {
        /** Switch remote request errors to HttpRequestExceptions */
        set_error_handler(array($updater, 'throwHttpRequestException'));
        $result = humbug_get_contents($this->remoteUrl);
        restore_error_handler();
        if (false === $result) {
            throw new HttpRequestException(sprintf(
                                             'Request to URL failed: %s', $this->remoteUrl
                                           ));
        }

        file_put_contents($updater->getTempPharFile(), $result);
    }

    /**
     * Retrieve the current version available remotely.
     *
     * @param Updater $updater
     * @return string|bool
     */
    public function getCurrentRemoteVersion(Updater $updater)
    {
        /** Switch remote request errors to HttpRequestExceptions */
        set_error_handler(array($updater, 'throwHttpRequestException'));
        $packageUrl = $this->getApiUrl();
        $package = json_decode(humbug_get_contents($packageUrl), true);
        restore_error_handler();

        if (null === $package || json_last_error() !== JSON_ERROR_NONE) {
            throw new JsonParsingException(
              'Error parsing JSON package data'
              . (function_exists('json_last_error_msg') ? ': ' . json_last_error_msg() : '')
            );
        }

        $versions = array_keys($package['package']['versions']);
        $versionParser = new VersionParser($versions);
        if ($this->getStability() === self::STABLE) {
            $this->remoteVersion = $versionParser->getMostRecentStable();
        } elseif ($this->getStability() === self::UNSTABLE) {
            $this->remoteVersion = $versionParser->getMostRecentUnstable();
        } else {
            $this->remoteVersion = $versionParser->getMostRecentAll();
        }

        /**
         * Setup remote URL if there's an actual version to download
         */
        if (!empty($this->remoteVersion)) {
            $this->remoteUrl = $this->getDownloadUrl($package);
        }

        return $this->remoteVersion;
    }

    /**
     * Retrieve the current version of the local phar file.
     *
     * @param Updater $updater
     * @return string
     */
    public function getCurrentLocalVersion(Updater $updater)
    {
        return $this->localVersion;
    }

    /**
     * Set version string of the local phar
     *
     * @param string $version
     */
    public function setCurrentLocalVersion($version)
    {
        $this->localVersion = $version;
    }

    /**
     * Set Package name
     *
     * @param string $name
     */
    public function setPackageName($name)
    {
        $this->packageName = $name;
    }

    /**
     * Get Package name
     *
     * @return string
     */
    public function getPackageName()
    {
        return $this->packageName;
    }

    /**
     * Set phar file's name
     *
     * @param string $name
     */
    public function setPharName($name)
    {
        $this->pharName = $name;
    }

    /**
     * Get phar file's name
     *
     * @return string
     */
    public function getPharName()
    {
        return $this->pharName;
    }

    /**
     * Set target stability
     *
     * @param string $stability
     */
    public function setStability($stability)
    {
        if ($stability !== self::STABLE && $stability !== self::UNSTABLE && $stability !== self::ANY) {
            throw new InvalidArgumentException(
              'Invalid stability value. Must be one of "stable", "unstable" or "any".'
            );
        }
        $this->stability = $stability;
    }

    /**
     * Get target stability
     *
     * @return string
     */
    public function getStability()
    {
        return $this->stability;
    }

    protected function getApiUrl()
    {
        return sprintf(self::API_URL, $this->getPackageName());
    }

    protected function getDownloadUrl(array $package)
    {
        $baseUrl = preg_replace(
          '{\.git$}',
          '',
          $package['package']['versions'][$this->remoteVersion]['source']['url']);
        $downloadUrl = sprintf('%s/downloads/%s', $baseUrl, $this->getPharName());
        return $downloadUrl;
    }


}