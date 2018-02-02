---
layout: default
title: "Generating changelogs"
permalink: /generate/changelogs
---

# Generate changelogs
---

Mill includes a `mill` command line application for doing various tasks on your API, including compiling your
documentation into a Markdown-representation changelog.

```bash
$ ./bin/mill changelog --help
Usage:
  changelog [options] [--] <output>

Arguments:
  output                 Directory to output your generated `changelog.md` file in.

Options:
      --config[=CONFIG]  Path to your `mill.xml` config file. [default: "mill.xml"]
  -h, --help             Display this help message
  -q, --quiet            Do not output any message
  -V, --version          Display this application version
      --ansi             Force ANSI output
      --no-ansi          Disable ANSI output
  -n, --no-interaction   Do not ask any interactive question
  -v|vv|vvv, --verbose   Increase the verbosity of messages: 1 for normal output, 2 for more verbose
                         output and 3 for debug

Help:
  Compiles a changelog from your API documentation.
```

Example usage:

```bash
$ ./vendor/bin/mill changelog blueprints/
Generating a changelog…

Done!
```

This will compile a `changelog.md` file into the `blueprints/` directory.

Looking at that file, we can see that we have changelog!

```bash
$ cat resources/examples/Showtimes/blueprints/changelog.md
# Changelog: Mill unit test API, Showtimes

## 1.1.3
### Added
#### Resources
- The GET on `/movie/{id}` can now throw the following errors:
    - `404 Not Found` with a `Error` representation: For no reason.
    - `404 Not Found` with a `Error` representation: For some other reason.
- The GET on `/movies/{id}` can now throw the following errors:
    - `404 Not Found` with a `Error` representation: For no reason.
    - `404 Not Found` with a `Error` representation: For some other reason.
- PATCH on `/movies/{id}` now returns a `404 Not Found` with a `Error` representation: If the trailer
    URL could not be validated.
- PATCH on `/movies/{id}` now returns a `202 Accepted` with a `Movie` representation.
- POST on `/movies` now returns a `201 Created`.

### Removed
#### Representations
- `external_urls.tickets` has been removed from the `Movie` representation.
…
```

### JSON
If you wish to get a JSON-encoded version of the changelog instead, you can use the changelog API directly.

```bash
$container = new \Mill\Container([
    'config.path' => '/path/to/your/mill.xml'
]);

$generator = new Mill\Generator\Changelog($container['config'], null);
$changelog = $generator->generateJson();
var_dump($changelog);
```

Mill wraps important pieces of content in the JSON-encoded changelog that can then be styled according to however you
want to render it:

| Changeset | HTML class | data-* attribute |
| :--- | :--- | :--- |
| Content-Type header | `mill-changelog_content_type` | `data-mill-content-type` |
| HTTP code | `mill-changelog_http_code` | `data-mill-http-code` |
| Representation | `mill-changelog_representation` | `data-mill-representation` |
| Representation field | `mill-changelog_field` | `data-mill-field` |
| Resource action method | `mill-changelog_method` | `data-mill-method` |
| Resource action parameter| `mill-changelog_parameter` | `data-mill-parameter` |
| Resource action URI | `mill-changelog_uri` | `data-mill-uri` |
| Resource namespaces | `mill-changelog_resource_namespace` | `data-mill-resource-namespace` |
