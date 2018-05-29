---
id: api-queryparam
title: "@api-queryparam"
---

A request parameter that can be supplied to a resource action via the query string.

> If you need to describe a parameter that can be used within a body payload, use the [`@api-param`](reference-api-param.md) annotation.

The syntax for this is exactly the same as [`@api-param`](reference-api-param.md).

## Examples
Using a token:

```php
@api-queryparam:public {page}
```

Using a token with available values:

```php
@api-queryparam:public {filter}
    + Members
        `embeddable`
        `playable`
```

With a vendor tag:

```php
@api-queryparam:public locked_down (string, needs:SomeApplicationFeature) - This is a cool thing.
```

Normal usage with acceptable values:

```php
@api-queryparam:private __testing (string) - This does a thing.
    + Members
        - `true`
        - `false`
```
