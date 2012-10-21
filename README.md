SlmLocale
===
SlmLocale is a Zend Framework 2 module to automatically detect a locale for your application. It uses a variety of pluggable strategies to search for a valid locale. SlmLocale features a default locale, a set of supported locales and locale aliases.

SlmLocale supports out of the box several strategies to search for a locale. Through interfaces, other strategies could be created. The set of default stragies is:

 1. Use the HTTP Accept-Language header
 2. Use a cookie
 3. Use first segment of the path of an uri
 4. Use sub domain name
 5. Use top level domain name

SlmLocale will also provide an optional integration with ZfcUser, to make it possible to set a default locale in a user "profile".

Installation
---
SlmLocale is available through composer. Add "slm/locale" to your composer.json list. During development of SlmLocale, you can specify the latest available version:

    "slm/locale": "dev-master"

In the `vendor/slm/locale/config` directory you can find a `slmlocale.global.php.dist` file. You can copy that file to `config/autoload/slmlocale.global.php` (note you have to omit the .dist extension). In that file you can tune every option from the detector and attach some strategies. To enable SlmLocale, mind to add `"SlmLocale"` to your application.config.php modules list.

Usage
---
Read about usage in the [documentation](https://github.com/juriansluiman/SlmLocale/blob/master/docs/1.Introduction.md).

Development
---
SlmLocale is at this moment under development. All new features of SlmLocale are made with test driven development and continuous integration from Travis-CI.

[![Build Status](https://secure.travis-ci.org/juriansluiman/SlmLocale.png?branch=master)](http://travis-ci.org/juriansluiman/SlmLocale)

If you notice any bugs in SlmLocale, please create an issue in [the tracker](https://github.com/juriansluiman/SlmLocale/issues). At this moment, several components are finished. Be aware you might encounter unwanted behaviour if you use SlmLocale in a production environment. The supplied strategies are not all finished yet:

 1. Http Accept-Language strategy: ready
 2. Cookie strategy: ready
 3. UriPathStrategy: under development
 4. Sub domain name strategy: under development
 3. Top level domain name strategy: not started
