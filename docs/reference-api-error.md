---
id: "api-error"
title: "@api-error"
---

This represents an exception that may be returned on a resource action.

## Syntax
```php
@api-error:visibility httpCode (\Representation<error code>, vendor:tagName) - description
```

## Requirements
| Required? | Needs a visibility | Supports versioning | Supports deprecation |
| :--- | :--- | :--- | :--- |
| × | ✓ | ✓ | × |

## Breakdown
| Tag | Optional | Description |
| :--- | :--- | :--- |
| :visibility | ✓ | [Visibility decorator](reference-visibility.md) |
| httpCode | × | The HTTP code that will be returned. Example: `404`, `403`, etc. |
| \Representation | × | The fully qualified class name for a representation that will be returned. |
| error code | ✓ | An optional error code, if your application supports unique error codes, that this error returns. This can either be a numerical code (ex. `(1234)`), or a fully qualified static accessor (ex. (`\Some\Exception::NOT_ALLOWED`). |
| vendor:tagName | ✓ | Defined vendor tag. See the [`@api-vendortag`](reference-api-vendortag.md) documentation for more information. There is no limit to the amount of vendor tags you can specify on a parameter. |
| description | ✓ | A short description describing why, or what, this error is. |

## Types and subtypes
In addition to supporting straight descriptions, the [`@api-error`](reference-api-error.md) annotation also supports the concept of "types" and "subtypes". For example:

```php
@api-error:public 404 (\ErrorRepresentation) - {user}
```

In this case, this exception will be thrown when the `{user}` passed into the route (usually via the URI) is not found. The generated error message for this becomes: "If the user cannot be found."

There also exist the concept of a subtype, represented as:

```php
@api-error:public 404 (\ErrorRepresentation) - {user,group}
```

This means that if the supplied group could not be found for the supplied user, an exception will be thrown. The generated error message for this is: "If the user cannot be found in the group."

## Examples
Usage with a vendor tag and description type:

```php
/**
 * ...
 *
 * @api-error:public 404 (\ErrorRepresentation, needs:SomeApplicationFeature) - {user}
 */
public function PATCH()
{
    ...
}
```

With an error code:

```php
/**
 * ...
 *
 * @api-error:public 403 (\ErrorRepresentation<7701>) If the user isn't
 *     allowed to do something.
 */
public function PATCH()
{
    ...
}
```

Standard usage:

```php
/**
 * ...
 *
 * @api-error:public 404 (\ErrorRepresentation) - If the user isn't allowed
 *     to do something.
 */
public function PATCH()
{
    ...
}
```
