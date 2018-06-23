---
id: deprecation
title: "Deprecation"
---

> **Note:** Deprecated status is not currently being used when compiling documentation, however there are [plans](https://github.com/vimeo/mill/milestones) to make this available.

## Usage
You might have instances where you need to deprecate a resource action request parameter or path, you can use the `:deprecated` "decorator".

```php
/**
 * Return information on a specific movie.
 *
 * @api-label Get a single movie.
 * @api-operationid getMovie
 * @api-group Movies
 *
 * @api-path:public /movies/+id
 * @api-path:private:deprecated /films/+id
 * @api-pathparam id (integer) - Movie ID
 *
 * @api-contenttype application/json
 * @api-scope public
 *
 * @api-return:public {object} \MyApplication\Representations\Movie
 *
 * @api-error:public 404 (\MyApplicationRepresentations\Error) - If the movie
 *     could not be found.
 */
public function GET()
{
    ...
}
```

Deprecated decorators are only available on [`@api-param`](reference-api-param.md), [`@api-queryparam`](reference-api-queryparam.md) and [`@api-path`](reference-api-path.md).
