---
layout: default
title: "Reference"
---

# Reference
---

* [Versioning]({{ site.github.url }}/reference/versioning)
* [Visibility]({{ site.github.url }}/reference/visibility)
* [Deprecation]({{ site.github.url }}/reference/deprecation)

## Resources

| @annotation | Description |
| :--- | :--- |
| [`@api-label`]({{ site.github.url }}/reference/api-label) | Short description of what the resource handles. |

## Resource Actions

| @annotation | Description |
| :--- | :--- |
| [`@api-capability`]({{ site.github.url }}/reference/api-capability) | Denotes a required API capability for the action. |
| [`@api-contenttype`]({{ site.github.url }}/reference/api-contenttype) | Denotes the `Content-Type` returned. |
| [`@api-error`]({{ site.github.url }}/reference/api-error) | An exception or error that may be thrown in the action. |
| [`@api-label`]({{ site.github.url }}/reference/api-label) | Short description of what the resource action handles. |
| [`@api-minversion`]({{ site.github.url }}/reference/api-minversion) | Minimum version required for this action. |
| [`@api-param`]({{ site.github.url }}/reference/api-param) | A request parameter for this action. |
| [`@api-return`]({{ site.github.url }}/reference/api-return) | A representation that is returned in a request. |
| [`@api-scope`]({{ site.github.url }}/reference/api-scope) | Required authentication token scope necessary for the action. |
| [`@api-uri`]({{ site.github.url }}/reference/api-uri) | Denotes a URI that this action services. |
| [`@api-urisegment`]({{ site.github.url }}/reference/api-urisegment) | Describes parameters for the URI. |

## Representations

| @annotation | Description |
| :--- | :--- |
| [`@api-data`]({{ site.github.url }}/reference/api-data) | Describes a piece of data in the representation that a resource action can return. |
| [`@api-see`]({{ site.github.url }}/reference/api-see) | A look-around reference that lets you link related documentation into a representation. |
