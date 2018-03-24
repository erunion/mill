---
layout: default
title: "Deprecation"
permalink: /reference/deprecation
---

# Deprecation
---

> **Note:** Deprecated status is not currently being used when generating documentation, however, there are plans to
> hook it up to the internal generator system to make it available in your compiled API Blueprints and manual Mill API
> usages.

You might have instances where you need to deprecate a resource action request parameter or URI, you can use the
`:deprecated` "decorator".

```php
/**
 * Return information on a specific movie.
 *
 * @api-label Get a single movie.
 *
 * @api-uri:public {Movies} /movies/+id
 * @api-urisegment {/movies/+id} id (integer) - Movie ID
 *
 * @api-uri:private:deprecated {Films} /films/+id
 * @api-urisegment {/films/+id} id (integer) - Film ID
 *
 * @api-contenttype application/json
 * @api-scope public
 *
 * @api-return:public {object} \MyApplication\Representations\Movie
 *
 * @api-error:public {404} \MyApplicationRepresentations\Error If the movie
 *     could not be found.
 */
public function GET()
{
    …
}
```

Deprecated decorators are only available on [`@api-param`]({{ site.github.url }}/reference/api-param) and
[`@api-uri`]({{ site.github.url }}/reference/api-uri).
