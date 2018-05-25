---
id: api-path
title: "@api-path"
---

This allows you to describe the path(s) that a resource action services.

## Syntax
```php
@api-path:visibility path
```

## Requirements

| Required? | Needs a visibility | Supports versioning | Supports deprecation |
| :--- | :--- | :--- | :--- |
| ✓ | ✓ | × | ✓ |

## Breakdown

| Tag | Optional | Description |
| :--- | :--- | :--- |
| path | × | This is the path that the resource action services. It's recommended that these conform to [RFC 3986](https://tools.ietf.org/html/rfc3986) and [RFC 6570](https://tools.ietf.org/html/rfc6570). |

## Examples
```php
/**
 * …
 * @api-path:private /movies
 *
 * @api-path:private:deprecated /theaters/+id/movies
 * …
 */
public function PATCH()
{
    …
}
```
