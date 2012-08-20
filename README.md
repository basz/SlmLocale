SlmLocale
===
SlmLocale is a Zend Framework 2 module to automatically detect a locale for your application. It uses a variety of pluggable strategies to search for a valid locale. SlmLocale has support for a default locale, a set of supported locales and locale aliases.

SlmLocale supports out of the box several strategies to search for a locale. Through interfaces, other strategies could be created. The set of default stragies is:

 1. Use top level domain name
 2. Use subdomain
 3. Use first segment of the path of an uri
 4. Use a cookie
 5. Use the HTTP Accept-Language header

SlmLocale will also provide an optional integration with ZfcUser, to make it possible to set a default locale in a user "profile".

Strategies
---
The strategies are triggered via an event manager. This gives the option to have strategies look very early and others as late as possible. For example, you might first want to search for a cookie, then for a domain name and as last option in the HTTP Accept-Language header.

The strategies can also be called when a locale is found. This is useful for example to write a cookie with the locale when the locale is found through the HTTP Accept-Language header. Or you might want to perform a redirect to the correct domain when a user stated it preferred a certain locale.

Detector options
---
The detector has a few options to tune the detection mechanism. First, there is a default locale. When every strategy sought for a locale, but did not find any, the default locale will be set. There is also a list of supported locales. Your application will probably not support every available locale, so you could define a set and SlmLocale tries to identify the best match. Aliases are possible to transform language codes into full locales. For example you can say if the code "en" is matched, the locale "en-US" will be used.

Installation
---
SlmLocale is available through composer. Add "slm/locale" to your composer.json list. During development of SlmLocale, you can specify the latest available version:

    "slm/locale": "dev-master"

In the `vendor/slm/locale/config` directory you can find a slmlocale.global.php.dist file. You can copy that file to `config/autoload/slmlocale.global.php` (note you have to omit the .dist extension now). In that file you can tune every option from the detector and attach some strategies. To enable SlmLocale, mind to add `"SlmLocale"` to your application.config.php modules list.
