---
layout: default
title: "@api-label"
permalink: /reference/api-label
---

# @api-label
A short description of what the resource, or resource action handles.

## Syntax
```php
@api-label description
```

## Requirements

| Required? | Needs a visibility | Supports versioning | Supports deprecation |
| :--- | :--- | :--- | :--- |
| ✓ | × | × | × |

## Breakdown

| Tag | Optional | Description |
| :--- | :--- | :--- |
| `description` | × | Description of what resource, or resource action handles. |

## Examples
On a resource:

```php
/**
 * @api-label Videos
 */
class Controller
{
    …
}
```

On a resource action:

```php
/**
 * @api-label Update data on a group of videos.
 * …
 */
public function PATCH()
{
    …
}
```
