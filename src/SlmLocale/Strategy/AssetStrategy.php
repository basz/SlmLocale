<?php

namespace SlmLocale\Strategy;

use SlmLocale\LocaleEvent;

/**
 * This class checks whether the requested uri should deliver an asset and should therefore not be redirected.
 * If it is an asset, we return false to stop further processing of other strategies in SlmLocale\Locale\Detector.
 *
 * Class AssetStrategy
 * @package SlmLocale\Strategy
 */
class AssetStrategy extends AbstractStrategy
{
    /** @var  array */
    protected $file_extensions;

    public function detect(LocaleEvent $event)
    {
        return null;
    }

    public function found(LocaleEvent $event)
    {
        $path      = $event->getRequest()->getUri();
        $extension = parse_url($path, PHP_URL_PATH);

        // if the file extension of the uri is found within the configured file_extensions, we do not rewrite and skip further processing
        if (in_array($extension, $this->file_extensions)) {
            return false;
        }
    }

    public function setOptions(array $options = [])
    {
        if (array_key_exists('file_extensions', $options)) {
            $this->file_extensions = (array) $options['file_extensions'];
        }
    }
}
