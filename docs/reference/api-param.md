---
layout: default
title: "@api-param"
permalink: /reference/api-param
---

# @api-param
---

A request parameter that can be supplied to a resource action.

## Syntax
```php
@api-param:visibility fieldName `sampleData` (type, required|optional, nullable, capabilityName) - Description
    + Members
        - `option` - Option description
```

## Requirements

| Required? | Needs a visibility | Supports versioning | Supports deprecation |
| :--- | :--- | :--- | :--- |
| × | ✓ | ✓ | ✓ |

## Breakdown

| Tag | Optional | Description |
| :--- | :--- | :--- |
| `:visibility` | ✓ | [Visibility decorator]({{ site.github.url }}/reference/visibility) |
| `fieldName` | × | This is the name of the variable that the developer should pass in the request. |
| `sampleData` | ✓ | This is a sample of what the contents of the parameter should be. For example, if you're passing in a number, this can be "50". |
| `type` | × | This can be a reference to the type of variable that is being passed in (string, boolean, array, etc.), or can be one of the [tokens](#tokens) that are configured for your API. |
| `required|optional` | ✓ | A flag that indicates that the parameter is, well, optional. If nothing is supplied, it defaults to being `optional`. |
| `nullable` | ✓ | A flag that indicates that the parameter is nullable. If nothing is supplied, it defaults to being non-nullable. |
| `capabilityName` | ✓ | Defined capability that the developers application should possess. |
| `Description` | × | Description for what the parameter is for. |
| Members | ✓ | If this parameter has acceptable values (like in the case of an `enum` type), you can document those values here along with a description for what the value is, or means. |

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

## <a name="tokens"></a>Tokens
Because writing out the same parameter for a large number of endpoints can get tiring, we have a system in place that
allows you to configure tokens, which act as kind of a short-code for a parameter:

In your [`mill.xml`]({{ site.github.url }}/configuration) file:

```xml
<parameterTokens>
    <token name="page">page (integer, optional) - The page number to show.</token>
    <token name="per_page">per_page (integer, optional) - Number of items to show on each page. Max 100.</token>
    <token name="filter">filter (string, optional) - Filter to apply to the results.</token>
</parameterTokens>
```

And then you can just reference the token as part of [`@api-param`]({{ site.github.url }}/reference/api-param):

```php
@api-param:public {page}
```

You can also pass in any enum values into tokens just as you would with a regular parameter:

```php
@api-param:public {filter}
    + Members
        `embeddable`
        `playable`
```

## Examples
Using a token:

```php
@api-param:public {page}
```

Using a token with available values:

```php
@api-param:public {filter}
    + Members
        `embeddable`
        `playable`
```

With a capability:

```php
@api-param:public locked_down (string, AnotherRequiredCapability) - This is a cool thing.
```

Normal usage with acceptable values:

```php
@api-param:public __testing (string) - This does a thing.
    + Members
        - `true`
        - `false`
```
