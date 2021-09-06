Holograph
=========

Holograph is a tool for managing the CSS documentation of a project, with
specific features that enable one to create and maintain a decent CSS style
guide. It is written in PHP and is similar to [hologram](https://github.com/trulia/hologram)
and [Knyle Style Sheets](http://warpspire.com/posts/kss/).

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
added in your CSS files. Additional Markdown files can also be added to create
the styleguide's documentation.

## CSS Files

The first step in getting use out of Holograph is to format comments blocks in
your CSS stylesheets. Add document blocks in your CSS files with some YAML
and some markdown as documented below.

```css
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
```

### Document Blocks

Any comment block starting with `/*doc` will be considered a document block,
and will be inspected and parsed by the styleguide generator.

```css
/*doc
This is my document block.
*/
```

### YAML Matter

A document block must have a YAML block at the top of the comment surrounded by
lines with three dashes (`---`). YAML is a convenient way to store key-value
pairs. The YAML block must contain at a minimum the `name` key.

```css
/*doc
---
name: media-object
---
*/
```

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

```css
/*doc
---
title: Badge Colors
parent: badge
name: badgeSkins
category: Components
---
*/
```

The `title` of the component is used when displaying the heading for that
component's documentation.

All components of a given `category` will be put into an HTML file with the
same name as the category. Using the default layout template (see below), each
category HTML file will be accessible in the main navigation of the outputed
styleguide. Note: If no category is provided, holograph will default the
category to `index` and thus the component documenation will be placed into
`index.html` of the rendered styleguide.

If a component is a `parent` (has child components), it will be displayed first,
followed by its child components on the rendered category page.

Example of parent/child definition:

```css
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
```

The above example defines a component named "badge" and a child component named
"badgeSkins." The badgeSkins documentation will be placed below the badge
documentation.

### HTML code examples

If your markdown documentation in your document blocks contains an fenced code
block with the type `html_example`, Holograph will output the sample HTML and
the HTML code in the styleguide. This provides a great way to document and
showcase example usage of your CSS classes.

Example:

    ```html_example
    <div class="page">
        <button class="btn">Click</button>
    </div>
    ```

## Markdown Files

You can also place Markdown files in the source directory. These files will be
processed and included in the destination directory.

Markdown files (*.md) will be renamed to *.html. For example `overview.md` will
be renamed to `overview.html` when included in the destination directory. Each
markdown file will also be added to the main navigation rendered to the styleguide.

Note: Holograph includes the [extra-extended](https://github.com/egil/php-markdown-extra-extended)
flavor of Markdown syntax, which suppors markdown syntax such as fenced code blocks
and tables.

## Configuration

Holograph uses a configuration file to define paths and other aspecst for the
current directory's build parameters.

The default name for the configuration file is `holograph.yml`. Holograph looks
for a file of this name in the current directory when building.

The following is the default config file.

```yml
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
```

### File paths

Here is an explanation of the file paths in the config file.

Path                 | Explanation
---------------------|---------------------
source               | The source path is a directory where the subject CSS and Markdown files reside
destination          | The destination path is where the styleguide will be written
documentation_assets | This is a path where the template files and styles for the styleguide itself are kept
dependencies         | Dependencies is an array of multiple paths, the contents of which should also
                     | be copied to the destination directory. Example: fonts, images used by the examples.
build                | The build path is where the preprocessor will write its output file(s).

### Documentation Assets

The documentation assets provides template files and CSS files used to render
the structure of the styleguide. By default, Holograph looks for a file called
`layout.html` in the documenation_assets directory. If none is found it will
use the default layout file in the `default-templates` directory in the Holograph
install directory.

The `layout.html` file can contain the following tokens that will be replaced
during the build step:

 - `{{title}}` - This will be replaced with the title defined in the config file
 - `{{main_stylesheet}}` - This will be replaced with the path to the stylesheet
                           generated by the preprocessor and represents the styles
                           you are wishing to document with the styleguide.
 - `{{navigation}}` - This will be replaced with a `<ul>` of the generated HTML files (categories)
 - `{{content}}` - This will be replaced with the content of the styleguide documentation.

You are encouraged to create a style guide layout file that will fit with your site's
branding or design. Any subfolders in the `documentation_assets` will also be copied
into the destination folder so you can include assets like fonts, images, css and
javascript to be used by the styleguide layout.

## Running holograph

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

### init

An easy way to create a clean `holograph.yml` config file in the current
directory is to run the `holograph init` command.

### config

Running `holograph config` will display the current configuration parameters in
the config file of the current directory.

### build

To build the styleguide from the source *.css and *.md files, run `holograph
build`. This will complete the following tasks:

1. read the config file
1. run the preprocessor (optimization step)
1. parse the CSS and Markdown files
1. generate the styleguide HTML files
1. copy the documentation assets and any other defined dependencies into the
   destination directory.

## The Preprocessor

The preprocessor is a mechanism to run a optimization step prior to building the
styleguide documentation. The current version only supports the options `minify`
or `none`. The "minify" option will use [Minify](https://github.com/mrclay/minify)
to minify the CSS files within the source directory and then combine them into one
file. This is currently a limited operation. It is expected that this functionality
will be expanded to support more complex configurations and other optimization
engines such as Sass or Less.

The following settings in the config file are used by the preprocessor:

    preprocessor: minify

The "preprocessor" key provides a directive of which minify option to use. Currently
the options are "minify" or "none".

    build: ./build/css

The "build" key defines a directory where the final optimized CSS file should be
written.

    main_stylesheet: build/css/screen.css

The "main_stylesheet" is the file path and name the preprocessor uses as the
final output file for the optimized stylesheet. This value is also used to
include the destination stylesheet in the styleguide build output.
