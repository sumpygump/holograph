Holograph
=========

Holograph is a tool for managing the CSS documentation of a project, with
specific features that enable one to create and maintain a decent CSS style
guide. It is similar to hologram and Knyle Style Sheets and is written in PHP.

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

#### Global Installation using Composer

An alternate way to install holograph so it is available on any project is by
installing it in your `~/.composer` directory using the following command:

    composer global require 'sumpygump/holograph=dev-master'

You must have `~/.composer/vendor/bin` in your environment PATH.

   $ export PATH=$PATH:~/.composer/vendor/bin

## Usage

The basic feature of holograph is to generate a style guide based on comments
added in your CSS files.

### Setup CSS files

Add document blocks in your CSS files with some YAML and some markdown.

    /*doc
    ---
    name: badgeSkins
    ---

    Badges are a way to display a small amount of text or an image within a
    nicely formatted box.

    Class          | Description
    -------------- | -----------------
    badgeStandard  | This is a basic badge
    badgePrimary   | This is a badge with the trulia orange used on CTAs
    badgeSecondary | This is a badge with the alternate CTA color (Trulia green)
    badgeTertiary  | This is a badge for a warning or something negative


    ```html_example
    <strong class="badgeStandard">Sold</strong>
    <strong class="badgePrimary">For Sale</strong>
    <strong class="badgeSecondary">For Rent</strong>
    ```

    */
    .badgeStandard {
      background-color: #999999;
    }

    .badgePrimary {
      background-color: #ff5c00;
    }

    .badgeSecondary {
      background-color: #5eab1f;
    }

#### Document Blocks

Any comment block starting with `/\*doc` will be considered a document block,
and will be inspected and parsed by the styleguide generator.

    /*doc
    This is my document block.
    */

#### YAML Matter

A document block must have a YAML block at the top of the comment surrounded by
lines with three dashes (`---`). YAML is a convenient way to store key-value
pairs. The YAML block must contain at a minimum the `name` key.

    /*doc
    ---
    name: media-object
    ---
    */

YAML block key-value pairs consist of a key name on the left followed by a
colon, a space, and then the value. For example: `name: button`

The following document block YAML keys are used by Holograph:

Key      | Meaning
-------- | ----------------------------------
title    | The title for this component
name     | A unique name for this component (required)
category | Which category this component appears, in the main navigation
parent   | The name of the parent's component, to define this component as a child

Full example:

    /*doc
    ---
    title: Badge Colors
    parent: badge
    name: badgeSkins
    category: Components
    ---
    */

The `title` of the component is used when displaying the heading for that
component's documentation.

All components of a given `category` will be put into an HTML file named with the
category. Each category file is accessible in the main navigation by default.

If a component is a `parent` (has child components), it will be displayed first,
followed by its child components.

Example:

    /*doc
    ---
    name: badge
    ---
    */

    /*doc
    ---
    name: badgeSkins
    parent: badge
    ---
    */

The above example defines a component named "badge" and a child component named
"badgeSkins." The badgeSkins documentation will be placed below the badge
documentation.

#### HTML code examples

If your markdown documentation in your document block contains an html code
block with the type `html\_example`, Holograph will output the sample HTML and
the HTML code in the styleguide. This provides a great way to document and
showcase example usage of your CSS classes.

Example:

    ```html_example
    <div class="page">
        <button class="btn">Click</button>
    </div>
    ```

### Configuration File

Holograph uses a configuration file to define paths and other aspecst for the
current directory's build parameters.

The default name for the configuration file is `holograph.yml`. Holograph looks
for a file of this name in the current directory when building.

The following is the default config file.

    # Holograph configuration

    # The title for this styleguide {{title}}
    title: 'Style Guide'

    # The directory containing the source files to parse
    source: ./components

    # The directory to generate files to
    destination: ./docs

    # Directory location of assets needed to accompany the docs (layout.html)
    documentation_assets: ./templates

    # Boolean indicating whether to use hologram compatibility
    # When true it will expect header.html and footer.html instead of layout.html
    compat_mode: false

    # Any other asset folders that need to be copied to the destination folder
    dependencies:
        - ./build

    # Build option to actually compiles CSS files (options: none, minify)
    preprocessor: minify

    # Directory to build the final CSS files
    build: ./build/css

    # The main stylesheet to be included {{main_stylesheet}}
    main_stylesheet: build/css/screen.css

### Running holograph

Holograph can be run from the command line

    $ holograph
    Holograph 0.7
    A markdown based build and documentation system for OOCSS

    Usage: holograph <action> [OPTIONS]

    Actions:
      init : Initialize environment for holograph (write conf file with defaults)
      config : Show current configuration parameters
      build : Build the style guide HTML/CSS
      help : Display program help and exit

    Options:
      -c <file> | --conf <file> : Use alternate configuration file
      -h | --help : Display program help and exit
      -q | --quiet : Quiet mode (Don't output anything)
      -v | --verbose : Verbose output mode
      --version : Display program version and exit
      --compat : Use hologram compatible mode (header.html/footer.html)

#### init

An easy way to create a clean `holograph.yml` config file in the current
directory is to run the `holograph init` command.

#### config

Running `holograph config` will display the current configuration parameters in
the config file of the current directory.

#### build

To build the styleguide from the source *.css and *.md files, run `holograph
build`. This will complete the following tasks:

1. read the config file
1. run the preprocessor (optimization step)
1. parse the CSS and Markdown files
1. generate the styleguide HTML files
1. copy the documentation assets and any other defined dependencies into the
   destination directory.
