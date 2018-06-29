---
id: "api-group"
title: "@api-group"
---

Name of the group that this action should be grouped under when generating documentation.

## Syntax
```php
@api-group groupName
```

## Requirements
| Required? | Needs a visibility | Supports versioning | Supports deprecation |
| :--- | :--- | :--- | :--- |
| ✓ | × | × | × |

## Breakdown
| Tag | Optional | Description |
| :--- | :--- | :--- |
| groupName | × | Group name |

## Examples
On a resource action:

```php
/**
 * @api-label Update data on a group of users.
 * @api-operationid updateUsers
 * @api-group Users
 *
 * @api-path:public /users
 *
 * ...
 */
public function PATCH()
{
    ...
}
```
