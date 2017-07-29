---
layout: default
title: "@api-throws"
permalink: /reference/api-throws
---

# @api-throws
---

This represents an exception that may be thrown inside of a resource action.

## Syntax
```php
@api-throws:visibility {http code} \Representation (error code) +capability+ description
```

## Requirements

| Required? | Needs a visibility | Supports versioning | Supports deprecation |
| :--- | :--- | :--- | :--- |
| × | ✓ | ✓ | × |

## Breakdown

| Tag | Optional | Description |
| :--- | :--- | :--- |
| `:visibility` | ✓ | [Visibility decorator]({{ site.github.url }}/reference/visibility) |
| `{http code}` | × | The HTTP code that will be returned. Example: `{404}`, `{403}`, etc. |
| `\Representation` | × | The fully qualified class name for a representation that will be returned. |
| `(error code)` | ✓ | An optional error code, if your application supports unique error codes, that this error returns. This can either be a numerical code (ex. `(1234)`), or a fully qualified static accessor (ex. (`\Some\Exception::NOT_ALLOWED`). |
| `description` | ✓ | A short description describing why, or what, this error is. |

## Types and subtypes
In addition to supporting straight descriptions, the [`@api-throws`]({{ site.github.url }}/reference/api-throws) annotation also supports
the concept of "types" and "subtypes". For example:

```php
@api-throws:public {404} \ErrorRepresentation {user}
```

In this case, this exception will be thrown when the `{user}` passed into the route (usually via the URI) is not found.
The generated error message for this becomes: "If the user cannot be found."

There also exist the concept of a subtype, represented as:

```php
@api-throws:public {404} \ErrorRepresentation {user,group}
```

This means that if the supplied group could not be found for the supplied user, an exception will be thrown. The
generated error message for this is: "If the user cannot be found in the group."

## Examples
Usage with a capability and description type:

```php
/**
 * …
 *
 * @api-throws:public {404} \ErrorRepresentation +SomeCapability+ {user}
 */
public function PATCH()
{
    …
}
```

With an error code:

```php
/**
 * …
 *
 * @api-throws:public {403} \ErrorRepresentation (\AppError::USER_NOT_ALLOWED)
 *     If the user isn't allowed to do something.
 */
public function PATCH()
{
    …
}
```

Standard usage:

```php
/**
 * …
 *
 * @api-throws:public {404} \ErrorRepresentation If the user isn't allowed to do
 *     something.
 */
public function PATCH()
{
    …
}
```
