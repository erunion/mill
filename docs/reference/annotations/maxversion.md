# @api-maxversion

This allows you to denote the maximum API version required for a resource action.

## Syntax
```php
@api-maxversion version
```

## Requirements
| Required? | Needs a visibility | Supports versioning | Supports deprecation |
| :--- | :--- | :--- | :--- |
| × | × | × | × |

## Breakdown
| Tag | Optional | Description |
| :--- | :--- | :--- |
| version | × | A specific, maximum API version required for a resource action. |

## Examples
```php
/**
 * @api-label Update a movie.
 * @api-operationid updateMovie
 * @api-group Movies
 *
 * @api-path:public /movies/+id
 * @api-pathparam id (integer) - Movie ID
 *
 * @api-maxversion 3.2
 *
 * @api-error:private 403 (\Some\ErrorErrorRepresentation) - If the user isn't
 *    allowed to do something.
 */
public function PATCH()
{
    ...
}
```
