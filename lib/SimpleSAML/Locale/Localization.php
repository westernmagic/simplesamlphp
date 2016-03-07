<?php

/**
 * Glue to connect one or more translation/locale systems to the rest
 *
 * @author Hanne Moa, UNINETT AS. <hanne.moa@uninett.no>
 * @package SimpleSAMLphp
 */

namespace SimpleSAML\Locale;

use Gettext\Translations;
use Gettext\Translator;

class Localization
{

    /**
     * The configuration to use.
     *
     * @var \SimpleSAML_Configuration
     */
    private $configuration;

    /**
     * The default gettext domain.
     */
    const DEFAULT_DOMAIN = 'ssp';

    /*
     * The default locale directory
     */
    private $localeDir;

    /*
     * Where specific domains are stored
     */
    private $localeDomainMap = array();


    /**
     * Constructor
     *
     * @param \SimpleSAML_Configuration $configuration Configuration object
     */
    public function __construct(\SimpleSAML_Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->localeDir = $this->configuration->resolvePath('locales');
        $this->language = new Language($configuration);
        $this->langcode = $this->language->getPosixLanguage($this->language->getLanguage());
        $this->i18nBackend = $this->configuration->getString('language.i18n.backend', null);
        $this->setupL10N();
    }

    /*
     * Add a new translation domain
     *
     * @param string $localeDir Location of translations
     * @param string $domain Domain at location
     */
    private function addDomain($localeDir, $domain)
    {
        $this->localeDomainMap[$domain] = $localeDir;
        $encoding = "UTF-8";
        if ($this->i18nBackend == 'twig.i18n') {
            bindtextdomain($domain, $localeDir);
            bind_textdomain_codeset($domain, $encoding);
        }
    }


    /**
     * Load translation domain from Gettext/Gettext using .po
     *
     * @param string $domain Name of domain
     */
    private function loadGettextGettextFromPO($domain = self::DEFAULT_DOMAIN) {
        $langcode = explode('_', $this->langcode)[0];
        $localeDir = $this->localeDomainMap[$domain];
        $poPath = $localeDir.'/'.$langcode.'/LC_MESSAGES/'.$domain.'.po';
        $translations = Translations::fromPoFile($poPath);
        $t = new Translator();
        $t->loadTranslations($translations);
        $t->register();
    }


    private function setupL10N() {
        // use old system
        if (is_null($this->i18nBackend)) {
            return;
        }
        // setup default domain
        // use gettext and Twig.I18n else gettextgettext
        if ($this->i18nBackend == 'twig.i18n') {
            putenv('LC_ALL='.$this->langcode);
            setlocale(LC_ALL, $this->langcode);
        }
        $this->addDomain($this->localeDir, self::DEFAULT_DOMAIN);
        $this->activateDomain(self::DEFAULT_DOMAIN);
    }


    /**
     * Set which translation domain to use
     *
     * @param string $domain Name of domain
     */
    public function activateDomain($domain)
    {
        if ($this->i18nBackend == 'twig.i18n') {
            textdomain($domain);
        } elseif ($this->i18nBackend == 'twig.gettextgettext') {
            $this->loadGettextGettextFromPO($domain);
        }
    }


    /**
     * Go back to default translation domain
     */
    public function restoreDefaultDomain()
    {
        if ($this->i18nBackend == 'twig.i18n') {
            textdomain(self::DEFAULT_DOMAIN);
        } elseif ($this->i18nBackend == 'twig.gettextgettext') {
            $this->loadGettextGettextFromPO(self::DEFAULT_DOMAIN);
        }
    }
}

