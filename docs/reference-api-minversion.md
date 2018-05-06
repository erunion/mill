---
id: api-minversion
title: "@api-minversion"
---

This allows you to denote the minimum API version required for a resource action.

## Syntax
```php
@api-minversion version
```

## Requirements

| Required? | Needs a visibility | Supports versioning | Supports deprecation |
| :--- | :--- | :--- | :--- |
| × | × | × | × |

## Breakdown

| Tag | Optional | Description |
| :--- | :--- | :--- |
| version | × | A specific, minimum API version required for a resource action. |

## Examples
```php
/**
 * @api-uri:public {Movies} /movies/+id
 * @api-urisegment {/movies/+id} id (integer) - Movie ID
 *
 * @api-minversion 3.2
 *
 * @api-throws:private {403} \Some\ErrorErrorRepresentation If the user isn't
 *    allowed to do something.
 */
public function PATCH()
{
    …
}
```
