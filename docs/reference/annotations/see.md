# @api-see

This is a reference pointer that allows you to pull in related documentation into a representation.

## Syntax
```php
@api-see \Class::method prefix
```

## Requirements
| Required? | Needs a visibility | Supports versioning | Supports deprecation |
| :--- | :--- | :--- | :--- |
| × | × | × | × |

## Breakdown
| Tag | Optional | Description |
| :--- | :--- | :--- |
| \Class::method | × | This is a fully qualified class name and method where the related representation that you want to import exists. |
| prefix | ✓ | If you want to prefix the imported documentation with a [`@api-data`](reference/annotations/data.md) name prefix, do so here. |

## Examples
```php
$representation = [
    ...

    /**
     * @api-data privacy (object) - Privacy settings
     * @api-see \MyApplication\SomeRepresentation::getPrivacy privacy
     */
    'privacy' => $this->getPrivacy($object, $request)

    ...
];
```

```php
private function getPrivacy($object, $request)
{
    return [
        /**
         * @api-data download (boolean) - Download permission setting
         */
        'download' => true
    ];
}
```

From here, `download` will be imported into the representation documentation as `privacy.download`.
