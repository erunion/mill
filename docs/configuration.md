# Configuration

In order to instruct Mill on where to look for documentation, and any constraints you may have, Mill requires the use of an XML configuration file: `mill.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<mill
    name="Movie showtimes API"
    bootstrap="vendor/autoload.php"
>
    <info>
        <terms url="https://example.com/terms" />

        <contact
            name="Get help!"
            email="support@example.com"
            url="https://developer.example.com/help" />

        <externalDocs>
            <externalDoc name="Developer Docs" url="https://developer.example.com" />
        </externalDocs>
    </info>

    <servers>
        <server environment="prod" url="https://api.example.com" description="Production" />
        <server environment="dev" url="https://api.example.local" description="Development" />
    </servers>

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
| name | ✓ | This is the canonical name of your API. |
| bootstrap | ✓ | Relative path to a PHP bootstrap file that will get loaded before Mill does any work. If running Mill programmatically, this can usually be a [Composer](https://getcomposer.org/) `vendor/autoload.php` file. |

## Settings
> All directory paths should be relative to the location of your `mill.xml` configuration file.

> If you specify a controller, representation, vendor tag, or authentication scope in your documentation that hasn't been configured here, documentation compiling will fail with errors.

### Authentication
The `<authentication>` element lets you configure specific authentication flows and OAuth 2 scopes for your API.

```xml
<authentication>
    <flows>
        <bearer format="bearer" />

        <oauth2>
            <authorizationCode url="/oauth/authorize" tokenUrl="/oauth/access_token" />
            <clientCredentials url="/oauth/authorize/client" />
        </oauth2>
    </flows>

    <scopes>
        <scope name="create" description="Create" />
        <scope name="delete" description="Delete" />
        <scope name="edit" description="Edit" />
        <scope name="public" description="Public" />
    </scopes>
</authentication>
```

#### Flows
Currently supported authentication flows are `bearer` and `oauth2`.

#### Scopes
If your API has an authentication system that requires a specific scope(s) for using an API endpoint, use this to document those.

You can find usage details for scopes in the [`@api-scope`](reference/annotations/scope.md) documentation.

### Compilers
The `<compilers>` element lets you control the documentation compilers that Mill supports from the [`compile`](compile-documentation.md) command.

#### Excludes
* Use `<exclude>` elements to specify a resource group that should be excluded from compiled specifications.
    * Make sure to add a `group` attribute so Mill knows what group you're excluding.

Example:

```xml
<compilers>
    <excludes>
        <exclude group="/" />
        <exclude group="OAuth" />
    </excludes>
</compilers>
```

### Controllers
The `<controllers>` setting lets you inform Mill on where your API controllers live.

* Use `<directory>` elements to specify a directory name (and `suffix`).
* Specify a `<class>` element for a specific, fully-qualified class name.
* Add in an `<excludes>` block, with `<class>` elements for excluding specific controllers from being parsed.

```xml
<controllers>
    <filter>
        <directory name="src/Controllers/" suffix=".php" />
    </filter>
</controllers>
```

### Info
The `<info>` element allows you to configure some information about your API:

* `<terms>`: A terms of service URL.
* `<contact>`: Contact information. `name` and `email` are optional.
* `<externalDocs>`: External API documentation you may want to surface to the end-user.

```xml
<info>
    <terms url="https://example.com/terms" />

    <contact
        name="Get help!"
        email="support@example.com"
        url="https://developer.example.com/help" />

    <externalDocs>
        <externalDoc name="Developer Docs" url="https://developer.example.com" />
    </externalDocs>
</info>
```

### Parameter Tokens
Parameter tokens allow you to create an [`@api-param`](reference/annotations/param.md) or [`@api-queryparam`](reference/annotations/queryparam.md)  shortcode to save time for common elements in your API (like paging or sorting).

Example:

```xml
<parameterTokens>
    <token name="page">page (integer, optional) - The page number to show.</token>
    <token name="per_page">per_page (integer, optional) - Number of items to show on each page. Max 100.</token>
    <token name="filter">filter (string, optional) - Filter to apply to the results.</token>
</parameterTokens>
```

You can find usage details for parameter tokens in the [`@api-param`](reference/annotations/param.md#tokens) and [`@api-queryparam`](reference/annotations/queryparam.md#tokens) documentation.

### Path Parameters
#### Translations
The path parameters translations section allows you to set up translation elements for [`@api-pathparam`](reference/annotations/pathparam.md) annotations. Say, in your code, the route for a video is at `/videos/+video_id`, but in your documentation, you want it to just say `/videos/+id`, this is the place to do that.

Example:

```xml
<pathParams>
    <translations>
        <translation from="id" to="video_id" />
    </translations>
</pathParams>
```

### Representations
The `<representations`> setting lets you inform Mill on where your API data representations (the content that your controllers return), live.

* Use `<directory>` elements to specify a directory name (and `suffix`).
  * Add in a `method` attribute so Mill knows the method to pull representation documentation from.
* Specify a `<class>` element for a specific, fully-qualified class name, and add a `method attribute`.
   * If the representation doesn't have a method, or documentation, you should add it to the `excludes` block.
* Add in an `<excludes>` block, with `<name>` elements for excluding specific controllers from being parsed.

```xml
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
```

#### Errors
The representation `<errors>` setting lets you tell Mill where your error representations are (the content that is returned from [`@api-error`](reference/annotations/error.md) annotations. Here you can specify a `<class>` with a fully-qualified class name.

Required attributes for the `<class>` element are:

* `method`: Same in the way that representations in your `<representations>` declaration have method attributes to tell Mill where your documentation lives, error representations require the same.
* `needsErrorCode`: Informs Mill if your error representation handles, and returns, a unique error code. The way that looks in your documentation is:

```php
/**
 * ...
 *
 * @api-error:public 403 (\ErrorRepresentation<7701>) - If the user isn't
 *     allowed to do something.
 */
public function PATCH()
{
    ...
}
```

Here, `\ErrorRepresentation` would have `needsErrorCode="true"`.

### Servers
Configure your API servers with the `<servers>` element.

```xml
<servers>
    <server environment="prod" url="https://api.example.com" description="Production" />
    <server environment="dev" url="https://api.example.local" description="Development" />
</servers>
```

### Tags
When building [resource actions](reference/resource-actions.md), you can use [`@api-group`](reference/annotations/group.md) to place your action within a specific group (or tag), that you can later use to reference. The `<tags>` config is here so you can configure your grouping tags, and potentially any additional metadata descriptions you'd like to attach to those in your compiled specifications.

```xml
<tags>
    <tag name="Movies">These resources help you handle movies.</tag>
    <tag name="Movies\Coming Soon" />
    <tag name="Theaters" />
</tags>
```

### Vendor tags
If you'd like to add additional metadata (that you can eventually filter your documentation against), you should use vendor tags to document those.

```xml
<vendorTags>
    <vendorTag name="tag:BUY_TICKETS" />
    <vendorTag name="tag:MOVIE_RATINGS" />
    <vendorTag name="tag:NONE" />
</vendorTags>
```

You can find usage details for vendor tags in the [`@api-vendortag`](reference/annotations/vendortag.md), [`@api-param`](reference/annotations/param.md), [`@api-queryparam`](reference/annotations/queryparam.md), [`@api-return`](reference/annotations/return.md), and [`@api-error`](reference/annotations/error.md) documentation.

### Versions
The `<versions>` setting lets you inform Mill on the various version of your API that exist. From here, Mill will then know what versions to compile documentation for.

To set a "default" API version, use the `default="true"` attribute. You **must** have a default version set, and there can only be one.

```xml
<versions>
    <version name="1.0" />
    <version name="1.1" default="true" />
    <version name="1.2" />
</versions>
```

## XML Schema Definition
If you wish to use it for a reference, Mill has an included [XML schema definition](https://github.com/erunion/mill/blob/master/config.xsd).
