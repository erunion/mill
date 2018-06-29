---
id: api-pathparam
title: "@api-pathparam"
---

This allows you to describe the parameters of a coupled resource action path.

## Syntax
```php
@api-pathparam paramName `sampleData` (type) - Description
    + Members
        - `option` - Value description
```

## Requirements
| Required? | Needs a visibility | Supports versioning | Supports deprecation |
| :--- | :--- | :--- | :--- |
| × | × | × | × |

## Breakdown
| Tag | Optional | Description |
| :--- | :--- | :--- |
| paramName | × | This is the name of the parameter that is used within the path. |
| sampleData | ✓ | This is a sample of what the contents of the parameter should be. For example, if you're passing in a number, this can be "50". |
| type | × | This can be a reference to the type of variable that is being passed in (string, boolean, array, etc.), into the coupled path. |
| description | × | Description of what the parameter is for. |
| Members | ✓ | If this path parameter has acceptable values (like in the case of an `enum` type), you can document those values here along with a description for what the value is, or means. |

### Supported Types
| Type | Specification representation |
| :--- | :--- |
| array | array |
| boolean | boolean |
| date | string |
| datetime | string |
| float | number |
| enum | enum |
| integer | number |
| number | number |
| object | object |
| string | string |
| timestamp | string |
| uri | string |

## Examples
```php
/**
 * ...
 *
 * @api-path:private:deprecated /movies/+id
 * @api-pathparam id `1234` (integer) - Movie ID
 *
 * ...
 */
public function PATCH()
{
    ...
}
```
