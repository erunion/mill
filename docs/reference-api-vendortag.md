---
id: "api-vendortag"
title: "@api-vendortag"
---

This defines a vendor tag in your API. With these, you can specify additional metadata (requirements specific to your API, notes like "requiresAUser", or anything else you can think of) on your resource actions or representation fields.

With Mill's generator commands, you can also later filter down your documentation to only those that have specific vendor tags.

## Syntax
```php
@api-vendortag vendorTagName
```

## Requirements

| Required? | Needs a visibility | Supports versioning | Supports deprecation |
| :--- | :--- | :--- | :--- |
| × | × | × | × |

## Breakdown

| Tag | Optional | Description |
| :--- | :--- | :--- |
| vendortag | × | Name of the vendor tag |

## Examples
On a resource action:

```php
/**
 * …
 * @api-vendortag needs:SomeApplicationFeature
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
     * @api-data download (boolean, needs:SomeApplicationFeature) - Download
     *      permission setting
     */
    'download' => true,

    …
];
```

