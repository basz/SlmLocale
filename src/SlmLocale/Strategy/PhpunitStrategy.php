<?php

namespace SlmLocale\Strategy;

use SlmLocale\LocaleEvent;

/**
 * This class checks if running in a phpunit environment. If so and phpunit is correctly configured, it will stop event processing as we cannot properly use this module with phpunit tests at the moment.
 * @SEE https://github.com/basz/SlmLocale/pull/99
 *
 * For configuration example have a look at README.md.
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

        $isPhpunit = array_key_exists('DISABLE_STRATEGIES', $_SERVER) && $_SERVER['DISABLE_STRATEGIES'];

        // if the file extension of the uri is found within the configured file_extensions, we do not rewrite and skip further processing
        if ($isPhpunit) {
            $event->stopPropagation();
        }
    }
}
