---
id: api-data
title: "@api-data"
---

This describes a piece of data within a representation that a resource action can return.

## Syntax
```php
@api-data fieldName `sampleData` (type, required|optional, nullable, vendor:tagName) - Description
    + Members
        - `option` - Option description
```

## Requirements
| Required? | Needs a visibility | Supports versioning | Supports deprecation |
| :--- | :--- | :--- | :--- |
| × | × | ✓ | × |

## Breakdown
| Tag | Optional | Description |
| :--- | :--- | :--- |
| fieldName | × | The data in a representation that you're documenting. So if your representation has a `link`, `field_name` would be `link`. If you're documenting nested objects, you should use dot-notation to map out the field name. So say you have a `metadata[connections][albums]` array in your representation, the `field_name` would then be `metadata.connections.albums`. |
| sampleData | ✓ | This is a sample of what the contents of the representation data can contain. For example, this returns a number, this can be "50". |
| type | × | This describes the type of data that a representation data field contains. |
| required&vert;optional | ✓ | A flag that indicates that the data is, well, optional. If nothing is supplied, it defaults to being `optional`. |
| nullable | ✓ | A flag that indicates that the data is nullable. If nothing is supplied, it defaults to being non-nullable. |
| vendor:tagName | ✓ | Defined vendor tag. See the [`@api-vendortag`](reference/api-vendortag.md) documentation for more information. There is no limit to the amount of vendor tags you can specify on a parameter. |
| Description | × | A description for that this data is. |
| Members | ✓ | If this data has acceptable values (like in the case of an `enum` type), you can document those values here along with a description for what the value is, or means. |

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

#### Representations
If your dataset is a Mill representation, you can document that by setting the type to the FQN of the representation in question.

```php
/**
 * @api-data pictures (\PictureRepresentation) - Pictures
 */
'pictures' => (new \PictureRepresentation)->create(),
```

#### Subtypes
Mill allows you, if necessary, to define a single subtype for a dataset. For example, if you have an array that contains objects, you can set the `type` as `array<object>`. Alternatively, if you have an array of representations, you can do the same with `array<\RepresentationFQN>`.

Currently, only `array` types are allowed to contain subtypes. To define subtypes of objects, use an `@api-data` annotation for each property.

## Examples
Common uses:

```php
$representation = [
    ...

    /**
     * @api-data uri (uri) - The canonical relative URI for the user.
     */
    'uri' => sprintf('/users/%d', $user->id),

    /**
     * @api-data metadata (object) - The users' metadata.
     */
    'metadata' => [
        /**
         * @api-data metadata.connections (object) - A list of resource URIs
         *     related to the user.
         */
        'connections' => [
            /**
             * @api-data metadata.connections.albums (object) - Information
             *     about the albums created by this user.
             */
            'albums' => [
                /**
                 * @api-data uri (uri) - API URI that resolves to the
                 *     connection data.
                 */
                'uri' => sprintf('/users/%s/albums', $user->id),

                /**
                 * @api-data options (array) - An array of HTTP methods
                 *     allowed on this URI.
                 */
                'options' => ['GET'],

                /**
                 * @api-data total (number) - Total number of items on
                 *     this connection.
                 */
                'total' => $user->getAlbums()->total
            ]
        ]
    ],

    ...
];
```

Documented enum:

```php
$representation = [
    ...

    /**
     * @api-data content_rating (enum) - MPAA rating
     *     + Members
     *         - `G`
     *         - `PG`
     *         - `PG-13`
     *         - `R`
     *         - `NC-17`
     *         - `X`
     *         - `NR`
     *         - `UR`
     */
    'content_rating' => $content_rating,

    ...
];
```

Array of objects:

```php
$representation = [
    ...

    /**
     * @api-data content_ratings (array<object>) - MPAA ratings
     */
    'content_ratings' => $content_ratings,

    ...
];
```

Representation:

```php
$representation = [
    ...

    /**
     * @api-data pictures (\PictureRepresentation) - Pictures
     */
    'pictures' => (new \PictureRepresentation)->create(),

    ...
];
```
