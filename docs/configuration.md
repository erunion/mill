---
layout: default
title: Configuration
permalink: /configuration
---

# Configuration
---

In order to instruct Mill on where to look for documentation, and any
constraints you may have, Mill requires the use of an XML configuration file
(`mill.xml`).

```xml
<?xml version="1.0" encoding="UTF-8"?>
<mill
    name="Movie showtimes API"
    bootstrap="vendor/autoload.php"
>
    <versions>
        <version name="1.0" />
        <version name="1.1" default="true" />
        <version name="1.2" />
    </versions>

    <controllers>
        <filter>
            <directory name="src/Controllers/" suffix=".php" />
        </filter>
    </controllers>

    <representations>
        <filter>
            <directory name="src/Representations/" method="create" suffix=".php"  />

            <excludes>
                <exclude name="\My\Application\Representations\Error" />
                <exclude name="\My\Application\Representations\CodedError" />
                <exclude name="\My\Application\Representations\Representation" />
            </excludes>
        </filter>

        <errors>
            <class name="\My\Application\Representations\Error" method="create" needsErrorCode="false" />
            <class name="\My\Application\Representations\CodedError" method="create" needsErrorCode="true" />
        </errors>
    </representations>
</mill>
```

## Options

| Option | Optional |Description |
| :--- | :--- | :--- |
| `name` | ✓ | This is the canonical name of your API. When you generate API Blueprint files, this will be the header declaration. |
| `bootstrap` | × | Relative path to a PHP bootstrap file that will get loaded before Mill does any work. This is usually a [Composer](https://getcomposer.org/) `vendor/autoload.php` file. This is necessary so Mill can access, and parse your API classes for documentation. |

## Settings
### Versions
The `<versions>` setting lets you inform Mill on the various version of your API
that exist. From here, Mill will then know what versions to compile
documentation for.

To set a "default" API version, use the `default="true"` attribute. You
**must** have a default version set, and there can only be one.

### Controllers
The `<controllers>` setting lets you inform Mill on where your API controllers
live.

* Use `<directory>` elements to specify a directory name (and `suffix`).
* Specify a `<class>` element for a specific, fully-qualified class name.
* Add in an `<excludes>` block, with `<class>` elements for excluding specific controllers from being parsed.

### Representations
The `<representations`> setting lets you inform Mill on where your API data representations (the content that your
controllers return), live.

* Use `<directory>` elements to specify a directory name (and `suffix`).
  * Add in a `method` attribute so Mill knows the method to pull representation documentation from.
* Specify a `<class>` element for a specific, fully-qualified class name, and add a `method attribute`.
   * If the representation doesn't have a method, or documentation, you should add it to the `excludes` block.
* Add in an `<excludes>` block, with `<name>` elements for excluding specific controllers from being parsed.

#### Errors
The representation `<errors>` setting lets you tell Mill where your error
representations are (the content that is returned from
[`@api-error`]({{ site.github.url }}/reference/api-error) annotations. Here you
can specify a `<class>` with a fully-qualified class name.

Required attributes for the `<class>` element are:

* `method`: Same in the way that representations in your `<representations>` declaration have method attributes to tell Mill where your documentation lives, error representations require the same.
* `needsErrorCode`: Informs Mill if your error representation handles, and returns, a unique error code. The way that looks in your documentation is:

```php
/**
 * …
 *
 * @api-error:public 403 (\ErrorRepresentation<7701>) - If the user isn't
 *     allowed to do something.
 */
public function PATCH()
{
    …
}
```

Here, `\ErrorRepresentation` would have `needsErrorCode="true"`.

### Scopes
If your API has an authentication system that requires a specific scope(s) for
using an API endpoint, use this to document those.

Example:

```xml
<scopes>
    <scope name="create" />
    <scope name="delete" />
    <scope name="edit" />
    <scope name="public" />
</scopes>
```

You can find usage details for scopes in the
[`@api-scope`]({{ site.github.url }}/reference/api-scope) documentation.

### Parameter Tokens
Parameter tokens allow you to create a
[`@api-param`]({{ site.github.url }}/reference/api-param) shortcode to save
time for common elements in your API (like paging or sorting).

Example:

```xml
<parameterTokens>
    <token name="page">page (integer, optional) - The page number to show.</token>
    <token name="per_page">per_page (integer, optional) - Number of items to show on each page. Max 100.</token>
    <token name="filter">filter (string, optional) - Filter to apply to the results.</token>
</parameterTokens>
```

You can find usage details for parameter tokens in the
[`@api-param`]({{ site.github.url }}/reference/api-param#tokens) documentation.

### URI Segments
#### Translations
The URI segment translations section allows you to set up translation elements
for [`@api-uriSegment`]({{ site.github.url }}/reference/api-urisegment)
annotations. Say, in your code, the route for a video is at `/videos/+video_id`,
but in your documentation, you want it to just say `/videos/+id`, this is the
place to do that.

Example:

```xml
<uriSegments>
    <translations>
        <translation from="id" to="video_id" />
    </translations>
</uriSegments>
```

### Vendor tags
If you'd like to add additional metadata (that you can eventually filter your
documentation against), you should use vendor tags to document those.

```xml
<vendorTags>
    <vendorTag name="tag:BUY_TICKETS" />
    <vendorTag name="tag:MOVIE_RATINGS" />
    <vendorTag name="tag:NONE" />
</vendorTags>
```

You can find usage details for vendor tags in the
[`@api-vendortag`]({{ site.github.url }}/reference/api-vendortag),
[`@api-param`]({{ site.github.url }}/reference/api-param),
[`@api-return`]({{ site.github.url }}/reference/api-return), and
[`@api-error`]({{ site.github.url }}/reference/api-error) documentation.

### Generators
These settings let you control the documentation generators that Mill supports
from the `./bin/mill generate` command.

#### API Blueprint
##### Excludes
* Use `<exclude>` elements to specify a resource namespace that should be excluded from API Blueprint generation and compilation.
    * Make sure to add a `namespace` attribute so Mill knows what namespace you're excluding.

Example:

```xml
<generators>
    <blueprint>
        <excludes>
            <exclude namespace="/" />
            <exclude namespace="OAuth" />
        </excludes>
    </blueprint>
</generators>
```

## Notes
* **All directory paths should be relative to the location of your `mill.xml` configuration file.**
* If you specify a controller, representation, vendor tag, or scope in your documentation that hasn't been configured here, API documentation generation will fail with errors.

## XSD
If you wish to use it for a reference, Mill has an included
[XML schema definition](https://github.com/vimeo/mill/blob/master/config.xsd).
