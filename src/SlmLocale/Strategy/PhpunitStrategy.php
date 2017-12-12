<?php

namespace SlmLocale\Strategy;

use SlmLocale\LocaleEvent;

/**
 * This class checks whether the requested uri should deliver an asset and should therefore not be redirected.
 * If it is an asset, we return false to stop further processing of other strategies in SlmLocale\Locale\Detector.
 *
 * Example config:
 * 'slm_locale' => [
 *     'default' => 'de_DE',
 *     'supported' => ['en_GB', 'de_DE'],
 *     'strategies' => [
 *         [
 *             'name' => SlmLocale\Strategy\AssetStrategy::class,
 *             'options' => [
 *                 'file_extensions' => [
 *                     'css', 'js'
 *                 ]
 *             ]
 *         ],
 *         'query',
 *         'cookie',
 *         'acceptlanguage'
 *     ],
 *     'mappings' => [
 *         'en' => 'en_GB',
 *         'de' => 'de_DE',
 *     ]
 * ],
 *
 * This example config would ignore the file_extensions ".css", ".CSS", ".js", ".JS".
 *
 * Class PhpunitStrategy
 * @package SlmLocale\Strategy
 */
final class PhpunitStrategy extends AbstractStrategy
{
    /** @var  array */
    private $file_extensions = [];

    public function detect(LocaleEvent $event)
    {
        $this->stopPropagationIfPhpunit($event);
    }

    public function found(LocaleEvent $event)
    {
        $this->stopPropagationIfPhpunit($event);
    }

    private function stopPropagationIfPhpunit(LocaleEvent $event)
    {
        if (! $this->isHttpRequest($event->getRequest())) {
            return;
        }

        $isPhpunit = array_key_exists('DISABLE_URIPATHSTRATEGY', $_SERVER) && $_SERVER['DISABLE_URIPATHSTRATEGY'];

        // if the file extension of the uri is found within the configured file_extensions, we do not rewrite and skip further processing
        if ($isPhpunit) {
            $event->stopPropagation();
        }
    }
}
