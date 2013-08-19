Holograph
=========

Holograph is a tool for managing the documentation of a PHP project, with
specific features that enable one to create and maintain a decent CSS style
guide. It is similar to hologram and Knyle Style Sheets.

## Features

 - Composable. Just drop in a require to your composer.json file and start
   running holograph.
 - Use as a build tool to generate a style guide as well as minify and
   combine CSS files.

## Installation

### Using Composer

Add the following to your `composer.json` file:

```json
{
    "require": {
        "sumpygump/holograph": "dev-master"
    }
}
```

Then run composer install to fetch.

    $ composer.phar install

If you don't have composer already installed, this is my recommendation for
installing it. See
[getcomposer.org installation instructions](http://getcomposer.org/doc/00-intro.md#globally).

```
$ curl -sS https://getcomposer.org/installer | php
$ sudo mv composer.phar /usr/local/bin/composer
```

Once installed, there is a CLI command in `vendor/bin/holograph`. The easiest
way to use holograph is to ensure that `./vendor/bin` is added to your system's
`$PATH`.

    $ export PATH=$PATH:./vendor/bin

## Usage

The basic feature of holograph is to generate a style guide based on comments
added in your CSS files.


