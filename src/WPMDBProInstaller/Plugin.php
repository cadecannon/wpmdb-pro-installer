<?php

namespace CadeCannon\WPMDBProInstaller;

use Composer\Composer;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PreFileDownloadEvent;
use Dotenv\Dotenv;
use CadeCannon\WPMDBProInstaller\Exceptions\MissingKeyException;

/**
 * A composer plugin that makes installing WPMDB PRO possible
 *
 * The WordPress plugin Advanced Custom Fields PRO (WPMDB PRO) does not
 * offer a way to install it via composer natively.
 *
 * This plugin uses a 'package' repository (user supplied) that downloads the
 * correct version from the WPMDB site using the version number from
 * that repository and a license key from the ENVIRONMENT or an .env file.
 *
 * With this plugin user no longer need to expose their license key in
 * composer.json.
 */
class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * The name of the environment variable
     * where the WPMDB PRO key should be stored.
     */
    const KEY_ENV_VARIABLE = 'WPMDB_PRO_KEY';

    /**
     * The name of the environment variable where the DOMAIN should be stored.
     * @var string
     */
    const DOMAIN_ENV_VARIABLE = 'DOMAIN_CURRENT_SITE';


    /**
     * @access protected
     * @var Composer
     */
    protected $composer;

    /**
     * @access protected
     * @var IOInterface
     */
    protected $io;

    /**
     * The function that is called when the plugin is activated
     *
     * Makes composer and io available because they are needed
     * in the addKeyAndDomain method.
     *
     * @access public
     * @param Composer $composer The composer object
     * @param IOInterface $io Not used
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * Subscribe this Plugin to relevant Events
     *
     * Pre Install/Update: The version needs to be added to the url
     *                     (will show up in composer.lock)
     * Pre Download: The key needs to be added to the url
     *               (will not show up in composer.lock)
     *
     * @access public
     * @return array An array of events that the plugin subscribes to
     * @static
     */
    public static function getSubscribedEvents()
    {
        return [
            //PackageEvents::PRE_PACKAGE_INSTALL => 'addVersion',
            //PackageEvents::PRE_PACKAGE_UPDATE => 'addVersion',
            PluginEvents::PRE_FILE_DOWNLOAD => 'addKeyAndDomain'
        ];
    }

    /**
     * Add the version to the package url
     *
     * The version needs to be added in the PRE_PACKAGE_INSTALL/UPDATE
     * event to make sure that different version save different urls
     * in composer.lock. Composer would load any available version from cache
     * although the version numbers might differ (because they have the same
     * url).
     *
     * @access public
     * @param PackageEvent $event The event that called the method
     * @throws UnexpectedValueException
     */
    public function addVersion(PackageEvent $event)
    {
        $package = $this->getPackageFromOperation($event->getOperation());

        if ($this->isSupportedPackage($package->getName())) {
            $version = $this->validateVersion($package->getPrettyVersion());
            $package->setDistUrl($this->addParameterToUrl($package->getDistUrl(), 't', $version));
        }
    }


    /**
     * Add the key from the environment to the event url
     *
     * The key is not added to the package because it would show up in the
     * composer.lock file in this case. A custom file system is used to
     * swap out the WPMDB PRO url with a url that contains the key.
     *
     * @access public
     * @param PreFileDownloadEvent $event The event that called this method
     * @throws MissingKeyException
     */
    public function addKeyAndDomain(PreFileDownloadEvent $event)
    {
        $processedUrl = $event->getProcessedUrl();

        if ($this->isSupportedUrl($processedUrl)) {
            $rfs = $event->getRemoteFilesystem();
            $acfRfs = new RemoteFilesystem(
                $this->addParameterToUrl($processedUrl,[
                    'licence_key' => $this->getKeyFromEnv(),
                    'site_url' => $this->getDomainFromEnv()
                ]),
                $this->io,
                $this->composer->getConfig(),
                $rfs->getOptions(),
                $rfs->isTlsDisabled()
            );
            $event->setRemoteFilesystem($acfRfs);
        }
    }

    /**
     * Get the package from a given operation
     *
     * Is needed because update operations don't have a getPackage method
     *
     * @access protected
     * @param OperationInterface $operation The operation
     * @return PackageInterface The package of the operation
     */
    protected function getPackageFromOperation(OperationInterface $operation)
    {
        if ($operation->getJobType() === 'update') {
            return $operation->getTargetPackage();
        }
        return $operation->getPackage();
    }

    /**
     * Validate that the version is an exact major.minor.patch.optional version
     *
     * The url to download the code for the package only works with exact
     * version numbers with 3 or 4 digits: e.g. 1.2.3 or 1.2.3.4
     *
     * @access protected
     * @param string $version The version that should be validated
     * @return string The valid version
     * @throws UnexpectedValueException
     */
    protected function validateVersion($version)
    {
        // \A = start of string, \Z = end of string
        // See: http://stackoverflow.com/a/34994075
        $major_minor_patch_optional = '/\A\d\.\d\.\d{1,2}(?:\.\d)?\Z/';

        if (!preg_match($major_minor_patch_optional, $version)) {
            throw new \UnexpectedValueException(
                'The version constraint of ' . self::WPMDB_PRO_PACKAGE_NAME .
                ' should be exact (with 3 or 4 digits). ' .
                'Invalid version string "' . $version . '"'
            );
        }

        return $version;
    }

    /**
     * Test if the given url is the WPMDB PRO download url
     *
     * @access protected
     * @param string The url that should be checked
     * @return boolean
     */
    protected function isSupportedUrl($url)
    {
        return preg_match('/https\:\/\/deliciousbrains.com\/dl\/wp-migrate-db-pro(-media-files|-cli|-multisite-tools)?-latest.zip/', $url);
    }


    /**
     * Test if the packae is a WPMDB Pro package
     *
     * @access protected
     * @param  string  $package The package URL
     * @return boolean
     */
    protected function isSupportedPackage($package)
    {
        return preg_match('/deliciousbrains\/wp-migrate-db-pro(-media-files|-cli|-multisite-tools)?/', $package);
    }

    /**
     * Get the WPMDB PRO key from the environment
     *
     * Loads the .env file that is in the same directory as composer.json
     * and gets the key from the environment variable KEY_ENV_VARIABLE.
     * Already set variables will not be overwritten by the variables in .env
     * @link https://github.com/vlucas/phpdotenv#immutability
     *
     * @access protected
     * @return string The key from the environment
     * @throws CadeCannon\WPMDBProInstaller\Exceptions\MissingKeyException
     */
    protected function getKeyFromEnv()
    {
        $this->loadDotEnv();
        $key = getenv(self::KEY_ENV_VARIABLE);

        if (!$key) {
            throw new MissingKeyException(self::KEY_ENV_VARIABLE);
        }

        return $key;
    }

    /**
     * Get the WPMDB PRO key from the environment
     *
     * Loads the .env file that is in the same directory as composer.json
     * and gets the domain from the environment variable DOMAIN_ENV_VARIABLE.
     * Already set variables will not be overwritten by the variables in .env
     * @link https://github.com/vlucas/phpdotenv#immutability
     *
     * @access protected
     * @return string The domain from the environment
     * @throws CadeCannon\WPMDBProInstaller\Exceptions\MissingDomainException
     */
    protected function getDomainFromEnv()
    {
        $this->loadDotEnv();
        $domain = getenv(self::DOMAIN_ENV_VARIABLE);

        if (!$domain) {
            throw new MissingKeyException(self::DOMAIN_ENV_VARIABLE);
        }

        return $domain;
    }

    /**
     * Make environment variables in .env available if .env exists
     *
     * getcwd() returns the directory of composer.json.
     *
     * @access protected
     */
    protected function loadDotEnv()
    {
        if (file_exists(getcwd().DIRECTORY_SEPARATOR.'.env')) {
            $dotenv = new Dotenv(getcwd());
            $dotenv->load();
        }
    }

    /**
     * Add a parameter to the given url
     *
     * Adds the given parameter at the end of the given url.
     *
     * @access protected
     * @param string $url The url that should be appended
     * @param string $parameters KVPs to add to the url
     * @return string The url appended with &parameter=value
     */
    protected function addParameterToUrl($url, array $params)
    {
        return $url . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }
}
