---
id: api-contenttype
title: "@api-contenttype"
---

This designates the HTTP `Content-Type` that a resource action returns.

## Syntax
```php
@api-contenttype content-type
```

## Requirements
| Required? | Needs a visibility | Supports versioning | Supports deprecation |
| :--- | :--- | :--- | :--- |
| ✓ | × | ✓ | × |

### Breakdown
| Tag | Optional | Description |
| :--- | :--- | :--- |
| content-type | × | HTTP Content-Type that a resource action returns. |

## Examples
```php
/**
 * ...
 *
 * @api-contenttype application/json
 *
 * ...
 */
public function PATCH()
{
    ...
}
```
