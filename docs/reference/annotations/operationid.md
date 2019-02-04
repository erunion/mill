# @api-operationid

A unique identifier for a resource action. This is used when compiling specifications.

## Syntax
```php
@api-operationid identifier
```

## Requirements
| Required? | Needs a visibility | Supports versioning | Supports deprecation |
| :--- | :--- | :--- | :--- |
| ✓ | × | × | × |

## Breakdown
| Tag | Optional | Description |
| :--- | :--- | :--- |
| identifier | × | Unique identifier for a resource action. |

## Examples
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
