---
id: versioning
title: "API Versioning"
---

As is the case with all API endpoints, there comes a time where you need to version specific endpoints, parameters, responses, exceptions, or representations. You can handle these types of changes in Mill by using the `@api-version` annotation on resource actions or representations.

## Usage
### Resource Actions
On resource actions, `@api-version` is a block-level annotation. This means that it allows you to classify any parameter following it as belonging to that defined version constraint. For example:

```php
@api-param:public {on_all_version requests}
@api-version >3.2
// these will only be greater than 3.2
@api-param:public {filter}
@api-param:public {page}

@api-version >=3.4
// this will be greater than 3.4
@api-param:private {foo}
```

* Anything below `@api-version >3.2` will be parsed with `version = >3.2` on it.
* Anything below `@api-version >=3.4` will have `version = >=3.4`.
* And anything that doesn't follow am `@api-version` annotation will be treated as being available across all versions.

Versioning is currently only supported on [`@api-param`](reference-api-param.md), [`@api-return`](reference-api-return.md), and [`@api-error`](reference-api-error.md).

### Representations
In representations, the [`@api-version`](reference-api-version.md) annotation works as any other annotation.

```php
/**
 * @api-data pictures (\MyApplication\Representations\Picture) - The users' pictures
 * @api-version >=3.2
 */
```

This response field will be then constrained to being available on anything above, or equal to, version `3.2`.

## Supported constraint schemas
The backend for the Mill versioning system uses the core [composer/semver](https://github.com/composer/semver) package from Composer, so standard [Semver](http://semver.org/) constraints will work, but you can see [their versions documentation](https://getcomposer.org/doc/articles/versions.md) for more proper information.
