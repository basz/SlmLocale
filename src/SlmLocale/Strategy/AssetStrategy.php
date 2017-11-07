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

    public function found(LocaleEvent $event)
    {
        $path = $event->getUri()->getPath();
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        // if the file extension is found within the uri, we do not rewrite and skip further processing
        if (in_array($extension, $this->file_extensions)) {
            return;
        }
    }

    public function setOptions(array $options = [])
    {
        if (array_key_exists('file_extensions', $options)) {
            $this->file_extensions = (array) $options['file_extensions'];
        }
    }

}