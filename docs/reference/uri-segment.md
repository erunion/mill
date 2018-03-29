---
layout: default
title: "@api-urisegment"
permalink: /reference/api-urisegment
---

# @api-urisegment
---

This allows you to describe the segments, or parameters, of a particular
resource action URI.

## Syntax
```php
@api-urisegment {uri} segmentName (type) - Description
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
| `uri` | × | This is the corresponding [`@api-uri`]({{ site.github.url }}/reference/api-uri) URI that this segment/parameter exists on. |
| `segmentName` | × | This is the name of the parameter that is used within the URI. |
| `type` | × | This can be a reference to the type of variable that is being passed in (string, boolean, array, etc.), into the accompanying URI. |
| `description` | × | Description of what the parameter is for. |
| Members | ✓ | If this URI segment has acceptable values (like in the case of an `enum` type), you can document those values here along with a description for what the value is, or means. |

### Supported Types

| Type | API Blueprint conversion |
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
 * …
 *
 * @api-uri:private:deprecated {Movies} /movies/+id
 * @api-urisegment {/movies/+id} id (integer) - Movie ID
 *
 * …
 */
public function PATCH()
{
    …
}
```
