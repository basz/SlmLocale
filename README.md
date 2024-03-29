SlmLocale
===
[![CI](https://github.com/basz/SlmLocale/actions/workflows/ci.yaml/badge.svg?branch=master)](https://github.com/basz/SlmLocale/actions/workflows/ci.yaml)
[![Latest Stable Version](https://poser.pugx.org/slm/locale/v/stable.png)](https://packagist.org/packages/slm/locale)
[![Coverage Status](https://coveralls.io/repos/github/basz/SlmLocale/badge.svg?branch=master)](https://coveralls.io/github/basz/SlmLocale?branch=master)

Created by Jurian Sluiman

Introduction
------------
SlmLocale is a Laminas module to automatically detect a locale for your
application. It uses a variety of pluggable strategies to search for a valid
locale. SlmLocale features a default locale, a set of supported locales and
locale aliases.

SlmLocale supports out of the box several strategies to search for a locale.
Through interfaces, other strategies could be created. The set of default
stragies is:

 1. The HTTP `Accept-Language` header
 2. A cookie to store the locale between several sessions of one visitor
 3. A query parameter to easily switch from locale
 4. The first segment of the path of an uri
 5. A part of the domain name (either the TLD or a subdomain)

Furthermore, it provides a set of additional localisation features:

 1. A default locale, used as fallback
 2. A set of aliases, so you can map `.com` as "en-US" in the host name strategy
 3. Redirect to the right domain/path when a locale is found
 4. View helpers to create a localised uri or a list of language switches

Installation
---
Add "slm/locale" to your composer.json file and update your dependencies. Enable
SlmLocale in your `application.config.php`.

If you do not have a composer.json file in the root of your project, copy the
contents below and put that into a file called `composer.json` and save it in
the root of your project:

```
{
    "require": {
        "slm/locale": ">=0.1.0,<1.2.0-dev"
    }
}
```

Then execute the following commands in a CLI:

```
curl -s http://getcomposer.org/installer | php
php composer.phar install
```

Now you should have a `vendor` directory, including a `slm/locale`. In your
bootstrap code, make sure you include the `vendor/autoload.php` file to properly
load the SlmLocale module.

Usage
---
Set your default locale in the configuration:

```
'slm_locale' => [
    'default' => 'nl-NL',
],
```

Set all your supported locales in the configuration:

```
'slm_locale' => [
    'supported' => ['en-US', 'en-GB'],
],
```

And enable some strategies. The naming is made via the following list:

 * **cookie**: `SlmLocale\Strategy\CookieStrategy`
 * **host**: `SlmLocale\Strategy\HostStrategy`
 * **acceptlanguage**: `SlmLocale\Strategy\HttpAcceptLanguageStrategy`
 * **query**: `SlmLocale\Strategy\QueryStrategy`
 * **uripath**: `SlmLocale\Strategy\UriPathStrategy`
 * **asset**: `SlmLocale\Strategy\AssetStrategy`

You can enable one or more of them in the `strategies` list. Mind the priority
is important! You usually want the `acceptlanguage` as last for a fallback:

```
'slm_locale' => [
    'strategies' => ['uripath', 'acceptlanguage'],
],
```

At this moment, the locale should be detected. The locale is stored inside php's
`Locale` object. Retrieve the locale with `Locale::getDefault()`. This is also
automated inside Laminas translator objects and i18n view helpers (so
you do not need to set the locale yourself there).

### Set the locale's language in html
It is common to provide the html with the used locale. This can be set for example
in the `html` tag:

```
<html lang="en">
```

Inject the detected language here with the following code:

```
<html lang="<?= $this->primaryLanguage()?>">
```

### Disable UriPathStrategy in PHPUNIT
This is necessary (at the moment) if you want to use ``this->dispatch('my/uri');`` in your `AbstractHttpControllerTestCase` unit tests.
Otherwise, if you check for responseCode you will get `302` where it should be `200`.

Example:
```
$this->dispatch('/to/my/uri');
$this->assertResponseStatusCode(200); // this will be 302 instead of 200

$this->dispatch('/en/to/my/uri');
$this->assertResponseStatusCode(200); // this will be 302 instead of 200
```

To fix add the following to your phpunit config.

phpunit.xml:
```
<phpunit...>
    ...
    <php>
        <server name="DISABLE_URIPATHSTRATEGY" value="true" />
    </php>
</phpunit>
```

Or set ``$_SERVER['DISABLE_URIPATHSTRATEGY'] = true;`` in your bootstrap file of phpunit. 

### Create a list of available locales

T.B.D

Read more about usage and the configuration of all the strategies in the
[documentation](docs/1.Introduction.md).
