---
layout: default
title: "@api-capability"
permalink: /reference/api-capability
---

# @api-capability
---

This defines a required capability in your API that the developer's application needs to possess in order to execute
the resource action, or get the representation field in the endpoint response.

## Syntax
```php
@api-capability capability
```

## Requirements

| Required? | Needs a visibility | Supports versioning | Supports deprecation |
| :--- | :--- | :--- | :--- |
| × | × | × | × |

## Breakdown

| Tag | Optional | Description |
| :--- | :--- | :--- |
| `capability` | × | Required capability that an API application must posses. |

## Examples
On a resource action:

```php
/**
 * …
 * @api-capability SomeApplicationCapability
 * …
 */
public function PATCH()
{
    …
}
```

On a representation field:

```php
$representation = [
    …

    /**
     * @api-data download (boolean, SomeApplicationCapability) - Download permission setting
     */
    'download' => true,

    …
];
```

