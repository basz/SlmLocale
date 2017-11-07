<?php
/**
 * Created by PhpStorm.
 * User: mfuesslin
 * Date: 07.11.17
 * Time: 11:18
 */

namespace SlmLocale\Strategy;


use SlmLocale\LocaleEvent;

class AssetStrategy extends AbstractStrategy
{
    /** @var  array */
    protected $file_extensions;

    public function detect(LocaleEvent $event)
    {
        return \Locale::getDefault();
    }

    public function found(LocaleEvent $event)
    {
        $path = $event->getRequest()->getUri();
        $path = parse_url($path, PHP_URL_PATH);
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        // if the file extension is found within the uri, we do not rewrite and skip further processing
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