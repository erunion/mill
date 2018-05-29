---
id: visibility
title: "Visibility"
---

> **Note:** Visibility status is not currently being used when generating API Blueprint specifications, however support is on the Mill [roadmap](https://github.com/vimeo/mill/milestones).

We have the concept of an annotation visibility "decorator" that allows you to set certain annotations as private, or explicitly public. With this additional metadata, you can do cool things like only show certain endpoints or parameters to privileged developers in your documentation.

## Usage
To choose what visibility your annotation should have, suffix your annotation with either `:public` or `:private`.

```php
/**
 * @api-group Movies

 * @api-path:public /movies/+id
 * @api-path:private /films/+id
 * @api-pathparam id (integer) - Movie ID
 *
 * @api-error:private 403 (\Some\ErrorErrorRepresentation) - If the user isn't
 *    allowed to do something.
 */
public function PATCH()
{
    …
}
```

Visibility decorators are required on [`@api-param`](reference-api-param.md), [`@api-queryparam`](reference-api-queryparam.md), [`@api-return`](reference-api-return.md), [`@api-error`](reference-api-error.md), and [`@api-path`](reference-api-path.md).
