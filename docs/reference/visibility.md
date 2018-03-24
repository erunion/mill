---
layout: default
title: "Visibility"
permalink: /reference/visibility
---

# Visibility
---

> **Note:** Visibility status is not currently being used when generating API Blueprint files, however there are
> future plans to hook it up to that generator.

We have the concept of an annotation visibility "decorator" that allows you to set certain annotations as private, or
explicitly public. With this additional metadata, you can do cool things like only show certain endpoints or parameters
to privileged developers in your documentation.

To choose what visibility your annotation should have, suffix your annotation with either `:public` or `:private`.

```php
/**
 * @api-uri:public {Movies} /movies/+id
 * @api-urisegment {/movies/+id} id (integer) - Movie ID
 *
 * @api-uri:private {Films} /films/+id
 * @api-urisegment {/films/+id} id (integer) - Film ID
 *
 * @api-error:private {403} \Some\ErrorErrorRepresentation If the user isn't
 *    allowed to do something.
 */
public function PATCH()
{
    …
}
```

Visibility decorators are required on [`@api-param`]({{ site.github.url }}/reference/api-param),
[`@api-return`]({{ site.github.url }}/reference/api-return), [`@api-error`]({{ site.github.url }}/reference/api-error),
and [`@api-uri`]({{ site.github.url }}/reference/api-uri).
