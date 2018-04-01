---
layout: default
title: "@api-scope"
permalink: /reference/api-scope
---

# @api-scope
---

This corresponds to an available user authentication token scope (ex. "create",
"edit", "interact`, etc.) that is required for a resource action, or a
representation data point.

## Syntax
```php
@api-scope scope description
```

## Requirements

| Required? | Needs a visibility | Supports versioning | Supports deprecation |
| :--- | :--- | :--- | :--- |
| × | × | × | × |

## Breakdown

| Tag | Optional | Description |
| :--- | :--- | :--- |
| `scope` | × | Authentication scope required for the resource action. |
| `description` | ✓ | Description for what the required scope is, or is used for. |

## Examples
```php
/**
 * @api-label Update a movie
 * @api-group Movies
 *
 * @api-uri:public /movies/+id
 * @api-urisegment {/movies/+id} id (integer) - Movie ID
 *
 * @api-scope edit
 *
 * @api-error:private 403 (\Some\ErrorErrorRepresentation) - If the user isn't
 *    allowed to do something.
 */
public function PATCH()
{
    …
}
```

```php
/**
 * @api-data director (\Mill\Examples\Showtimes\Representations\Person) - Director
 * @api-scope public
 */
```
