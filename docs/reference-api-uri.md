---
id: api-uri
title: "@api-uri"
---

This allows you to describe the URI(s) that a resource action services.

## Syntax
```php
@api-uri:visibility {namespace} uri
```

## Requirements

| Required? | Needs a visibility | Supports versioning | Supports deprecation |
| :--- | :--- | :--- | :--- |
| ✓ | ✓ | × | ✓ |

## Breakdown

| Tag | Optional | Description |
| :--- | :--- | :--- |
| {namespace} | × | A namespace which this action lives under. This is used for grouping your documentation into like groups (like having your user actions grouped under `User`). It also allows you to do sub-grouping with `Namespace\Secondary Namespace`, if you wish. There is no limit to the amount of depths a URI can be in. |
| uri | × | This is the URI that the resource action services. It's recommended that these conform to [RFC 3986](https://tools.ietf.org/html/rfc3986) and [RFC 6570](https://tools.ietf.org/html/rfc6570). |

## Examples
```php
/**
 * …
 * @api-uri:private {Movies} /movies
 *
 * @api-uri:private:deprecated {Theaters\Movies} /theaters/+id/movies
 * …
 */
public function PATCH()
{
    …
}
```
