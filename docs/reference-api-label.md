---
id: api-label
title: @api-label
---

A short description of what a representation, or resource action action, handles.

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
| description | × | Description of what resource, or resource action handles. |

## Examples
On a representation:

```php
/**
 * Data representation for a specific movie.
 *
 * @api-label Movie
 */
class Movie extends Representation
{
    ...
}
```

On a resource action:

```php
/**
 * Update a movies data.
 *
 * @api-label Update a movie.
 * @api-operationid updateMovie
 * @api-group Movies
 *
 * @api-path:public /movies/+id
 * @api-pathparam id `1234` (integer) - Movie ID
 *
 * ...
 */
public function PATCH()
{
    ...
}
```
