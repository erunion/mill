---
layout: default
title: "@api-uri"
permalink: /reference/api-uri
---

# @api-uri
---

This allows you to describe the URI(s) that a resource action services.

## Syntax
```php
@api-uri:visibility uri
```

## Requirements

| Required? | Needs a visibility | Supports versioning | Supports deprecation |
| :--- | :--- | :--- | :--- |
| ✓ | ✓ | × | ✓ |

## Breakdown

| Tag | Optional | Description |
| :--- | :--- | :--- |
| `uri` | × | This is the URI that the resource action services. It's recommended that these conform to [RFC 3986](https://tools.ietf.org/html/rfc3986) and [RFC 6570](https://tools.ietf.org/html/rfc6570). |

## Examples
```php
/**
 * …
 * @api-uri:private /movies
 *
 * @api-uri:private:deprecated /theaters/+id/movies
 * …
 */
public function PATCH()
{
    …
}
```
