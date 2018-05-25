---
id: api-return
title: "@api-return"
---

Like a standard [PHPDoc](https://phpdoc.org/) [`@return`](https://phpdoc.org/docs/latest/references/phpdoc/tags/return.html) annotation, this defines the response, and representation that a resource action returns. The difference here, however, is that this is made up of two parts:

* Return type
* Representation

The return type should be indicative of the HTTP code that will be delivered (ex. "collection", "object", "created",
etc.), and the representation should be representative of the type of response and data that this action deals with.
Say if this is a user data action, it might return a `\UserRepresentation`.

## Syntax
```php
@api-return:visibility {return type} \Representation description
```

## Requirements

| Required? | Needs a visibility | Supports versioning | Supports deprecation |
| :--- | :--- | :--- | :--- |
| × | ✓ | ✓ | × |

## Breakdown

| Tag | Optional | Description |
| :--- | :--- | :--- |
| :visibility | ✓ | [Visibility decorator](reference-visibility.md) |
| {return type} | × | The type of response that will be returned. Example: `{ok}`, `{accepted}`, `{notmodified}`, etc. |
| \Representation | ✓ | The fully qualified class name for a representation that will be returned. If your action is handling things like a `{delete}` or `{notmodified}` call, that normally don't return any data, you can exclude this. |
| description | * | A short description describing why, or what, this response is. A description is only required when the returning HTTP code for this return is non-200. |

## Available return types

| HTTP Code | Designator |
| :--- | :--- |
| 200 OK | collection |
| | directory |
| | object |
| 201 Created | clip |
| | created |
| 202 Accepted | accepted |
| 204 No Content | added |
| | deleted |
| | exists |
| | updated |
| 304 Not Modified | notmodified |

> **Note:** `@api-return` does not support returning 400 or 500 error codes. If you need those, use [`@api-error`](reference-api-error.md) instead.

## Examples
```php
/**
 * …
 *
 * @api-return:private {accepted} \Some\Representation
 */
public function PATCH()
{
    …
}
```

```php
/**
 * …
 *
 * @api-public {notmodified} If no content has changed since the last modified date.
 */
public function GET()
{
    …
}
```
