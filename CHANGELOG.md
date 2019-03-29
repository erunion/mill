# Changelog
## [5.1.7] - 2019-03-29
### Fixed
* String boolean examples are now compiled as proper booleans into OpenAPI specs.

## [5.1.6] - 2019-03-04
### Changed
* Reverting changes made in [5.1.5]. [#227](https://github.com/vimeo/mill/pull/227)

## [5.1.5] - 2019-02-29
### Changed
* Some more minor changes to how we document OpenAPI responses that have multiple descriptions. [#226](https://github.com/vimeo/mill/pull/226)

## [5.1.4] - 2019-02-27
### Changed
* Cleaned up how we document OpenAPI responses that have multiple descriptions. [#225](https://github.com/vimeo/mill/pull/225)

## [5.1.3] - 2019-02-05
### Added
* Added a new `--latest` flag to the `compile` command for compiling only your latest specifications. [#222](https://github.com/vimeo/mill/pull/222)

## [5.1.2] - 2018-12-04
### Changed
* Compiled OpenAPI specifications now target OpenAPI 3.0.2. [#216](https://github.com/vimeo/mill/pull/216)

### Fixed
* Examples in OpenAPI specifications will now match the associated type. Booleans will be booleans, ints ints, floats floats. [#215](https://github.com/vimeo/mill/pull/215)

## [5.1.1] - 2018-12-04
### Fixed
* Fixed a bug where using the `--for_public_consumption` `compile` argument would include documentation that was vendor tag-bound. [5d417f0](https://github.com/vimeo/mill/commit/5d417f07b2dcdda3d8264c9556d9c4ee7ef63cfb)

## [5.1.0] - 2018-12-04
### Added
* Addition of a new `--for_public_consumption` argument to the `compile` command that lets you compile public API specifications for public consumption. [#214](https://github.com/vimeo/mill/pull/214)

## [5.0.2] - 2018-12-04
### Changed
* The `bootstrap` declaration in `mill.xml` is no longer required. [#213](https://github.com/vimeo/mill/pull/213)

## [5.0.1] - 2018-11-15
### Fixed
* Resolved an issue in the config FQN parser where it could improperly locate class names. [#210](https://github.com/vimeo/mill/pull/210)

## [5.0.0] - 2018-11-13
### Added
* The [Contributor Covenant Code of Conduct](https://www.contributor-covenant.org/).
* Human-friendly schema names are now compiled into the `schemas` section in OpenAPI specs. [#201](https://github.com/vimeo/mill/pull/201)
* `@api-data` annotations now support `required` and `optional` flags for signaling that that piece of data is expected to always be returned in the response. The default behavior is for the annotation to be `required`. Signaling something as optional is opt-in and requires the `optional` flag. [#203](https://github.com/vimeo/mill/pull/203), [#204](https://github.com/vimeo/mill/issues/204)
* The `compile` command now has the ability to compile specifications for public-only or vendor-tagged documentation. [#198](https://github.com/vimeo/mill/issues/198)

### Changed
* Eliminating the need for `@api-label` annotations on resources. [#194](https://github.com/vimeo/mill/pull/194)
* Resource methods in compiled OpenAPI specs are now sorted alphabetically.
* Compiler has been reworked to compile documentation from the resource level down. This resolves a security bug where representations that were only ever used in a private manner would be exposed to public doc generation. [#199](https://github.com/vimeo/mill/pull/199)
* Subtypes are now required for `array` types. [#190](https://github.com/vimeo/mill/issues/190)

### Fixed
* Resolved a FQN parsing bug that sometimes occurred when there were long `use` statements at the top of a file. [#192](https://github.com/vimeo/mill/issues/192)

## [4.2.13] - 2018-07-26
## Fixed
- Fixed a bug in OpenAPI compilation where subtyped-arrays were always compiled as `string`. [#191](https://github.com/vimeo/mill/pull/191)

## [4.2.12] - 2018-07-25
## Added
- Added operation IDs into compiled changelog `<span>` elements. [#189](https://github.com/vimeo/mill/pull/189)

## [4.2.11] - 2018-07-20
## Changed
- Making the TagReducer prefix matching a little less generous [#188](https://github.com/vimeo/mill/pull/188)

## [4.2.10] - 2018-07-19
## Fixed
- Fixing a logic bug where `x-mill-visibility-private` extensions were set to `true` on paths that were public, and private not having them at all. [#178](https://github.com/vimeo/mill/pull/186)
- No longer removing nested property declarations in data models, fixing a bug where required fields would sometimes lose their requirement tags. [#187](https://github.com/vimeo/mill/pull/187)

## [4.2.9] - 2018-07-18
## Fixed
* Fixed some quirks with query parameters that had subtypes sometimes being compiled into invalid OpenAPI files. [#186](https://github.com/vimeo/mill/pull/186)

## [4.2.8] - 2018-07-17
## Added
* Compiled OpenAPI specifications now have vendor tags, `x-mill-vendor-tag`, added to any parameters, request body, or response data that have them. [#185](https://github.com/vimeo/mill/pull/185)

## Changed
* Compiled OpenAPI specifications' schema models now have their properties sorted alphabetically. [#185](https://github.com/vimeo/mill/pull/185)

## [4.2.7] - 2018-07-17
## Changed
* Component schemas are now sorted alphabetically in compiled OpenAPI specifications. [#184](https://github.com/vimeo/mill/pull/184)

## [4.2.6] - 2018-07-14
## Added
* Added `x-mill-path-aliased` and `x-mill-path-aliases` extensions to compiled OpenAPI specs. [#183](https://github.com/vimeo/mill/pull/183)

## [4.2.5] - 2018-07-11
## Fixed
* Removed some bad `TagReducer` unit tests. [d7c2c2c](https://github.com/vimeo/mill/commit/d7c2c2cc6fe9abfb770dab72b11f4b920a87c8e1)

## [4.2.4] - 2018-07-11
## Fixed
* Loose tag reductions through the `TagReducer` are no longer case-sensitive. [#182](https://github.com/vimeo/mill/pull/182)

## [4.2.3] - 2018-07-11
## Added
* Allowing OpenAPI specs to be reduced, with the `TagReducer`, with looser tag names. [#181](https://github.com/vimeo/mill/pull/181)

## [4.2.2] - 2018-07-11
### Fixed
* Paths in compiled OpenAPI specification are now in alphabetical order. [#180](https://github.com/vimeo/mill/pull/180)

## [4.2.1] - 2018-07-10
### Changed
* Loosened the [symfony/yaml](https://packagist.org/packages/symfony/yaml) requirements to allow `^2.0|^3.0|^4.0` instead of only `^2.0`. [#179](https://github.com/vimeo/mill/pull/179)

## [4.2.0] - 2018-07-03
### Added
* Adding `x-mill-path-template` for outputting the raw path template to the specification. Can be helpful if you want to utilize [RFC 6570](https://tools.ietf.org/html/rfc6570) templates in your specification usage. [#177](https://github.com/vimeo/mill/pull/177)
* A new `environment` attribute on `server` config entries. [#177](https://github.com/vimeo/mill/pull/177)
* New `--environment` flag on `./bin/mill compile` that lets you compile OpenAPI specifications for specifically configured server environments. [#177](https://github.com/vimeo/mill/pull/177)

### Changed
* Renaming `x-mill-deprecated` to `deprecated`. [#177](https://github.com/vimeo/mill/pull/177)
* Renaming `x-mill-vendortags` to `x-mill-vendor-tags`. [#177](https://github.com/vimeo/mill/pull/177)

## [4.1.0] - 2018-06-29
### Added
* A new `@api-operationid` annotation for setting unique operation IDs on resource actions. [#174](https://github.com/vimeo/mill/pull/174)
* OpenAPI compilation now generates individual specifications for each tag (`@api-group`). [#176](https://github.com/vimeo/mill/pull/176)

### Fixed
* Max/min version annotations now properly support Semver patch versions during compilation. [#176](https://github.com/vimeo/mill/pull/176)

## [4.0.0] - 2018-06-17
### Added
* OpenAPI compilation support. [#16](https://github.com/vimeo/mill/issues/16)
* A new `@api-vendortag` annotation, replacing `@api-capability`. [#148](https://github.com/vimeo/mill/issues/148)
* `@api-maxversion`. Same as `@api-minversion`, but the opposite. [#161](https://github.com/vimeo/mill/issues/161)
* Added additional relevant data to request parameters in JSON-generated changelogs. [#107](https://github.com/vimeo/mill/issues/107)
* Support for subtypes to `@api-param`. [#159](https://github.com/vimeo/mill/issues/159)
* A new `@api-queryparam` annotation for documenting parameters that are to be used in query strings. `@api-param` now represents body payloads. [#109](https://github.com/vimeo/mill/issues/109)
* New configurations: `authentication`, `info` and `servers` [#173](https://github.com/vimeo/mill/pull/173)

### Changed
* The `generate` command is now called `compile`. [#16](https://github.com/vimeo/mill/issues/16)
* Renamed `@api-throws` to `@api-error`. [#141](https://github.com/vimeo/mill/issues/141)
* `@api-error` annotations now have a new MSON syntax. [#155](https://github.com/vimeo/mill/pull/155)
* `@api-uri` is now `@api-path`. [#162](https://github.com/vimeo/mill/pull/162)
* `@api-urisegment` is now `@api-pathparam`. [#162](https://github.com/vimeo/mill/pull/162)
* The `scopes` config is now nested within `authentication`. [#173](https://github.com/vimeo/mill/pull/173)
* The `generators` config is now called `compilers`. [#16](https://github.com/vimeo/mill/issues/16)

### Fixed
* Now throwing an exception when `enum` annotations (annotations with member values) are written as `string`. [#150](https://github.com/vimeo/mill/issues/150)
* Subtypes are now being properly converted to API Blueprint-compatible types during API Blueprint compilation. [#147](https://github.com/vimeo/mill/issues/147)
* Scopes are now passed down through `@api-see` annotations, much like `@api-version` is already. [#140](https://github.com/vimeo/mill/issues/140)
* Multi-line enum descriptions are now supported. [#160](https://github.com/vimeo/mill/issues/160)
* Non-aliased paths can no longer be selected as the primary path for a resource action. [#139](https://github.com/vimeo/mill/issues/139)

### Removed
* `@api-capability` has been removed and replaced with a new `@api-vendorTag` system. [#148](https://github.com/vimeo/mill/issues/148)

## [3.0.2] - 2018-03-21
### Fixed
- Cleaned up some API Blueprint verbage surrounding representation scopes.

## [3.0.1] - 2018-02-27
### Changed
- `symfony/console` requirement is now `^3.2 || ^4.0`.
- Upgraded `vimeo/psalm` to v1.0

## [3.0.0] - 2018-02-01
### Added
- A new object hydration system. [#127](https://github.com/vimeo/mill/issues/127)
- `./mill --version` will now display the version of Mill you have installed. [#131](https://github.com/vimeo/mill/issues/131)

### Changed
- Upped the library requirement to PHP 7.1 [#13](https://github.com/vimeo/mill/issues/13)
- Renamed `@api-uri` "group" arguments to "namespace". [#132](https://github.com/vimeo/mill/issues/132)

### Fixed
- Support for multiple `@api-see` on the same representation data. [#134](https://github.com/vimeo/mill/issues/134)
- Sample data that was `0` would not get generated into API Blueprint files. [#125](https://github.com/vimeo/mill/issues/125)
- Private documentation could be exposed in changelogs. [#119](https://github.com/vimeo/mill/issues/119)

## [2.6.4] - 2018-01-11
### Changed
- Slightly revised the wording of our changelog templates.

## [2.6.3] - 2017-12-12
### Added
- Returning `visible` in `ReturnAnnotation->toArray()` calls.

## [2.6.2] - 2017-11-03
### Changed
- Slightly altered the wording on scope and multi-exception entries in generated API Blueprint files.

## [2.6.1] - 2017-09-02
### Fixed
- Fixed a bug where annotations that were documented as being public, but behind a capability were being exposed under some circumstances.

## [2.6.0] - 2017-08-25
### Added
- A new `errors` command for generating Markdown representation of all documented API errors.
- Support for `nullable` flags in MSON-supported annotations. [#104](https://github.com/vimeo/mill/issues/104)

### Fixed
- Compatibility issues with `enum` parameter types in compiled API Blueprint files. [#113](https://github.com/vimeo/mill/issues/113)

## [2.5.6] - 2017-08-18
### Changed
- Changelog versions are now sorted with SemVer rules.

## [2.5.5] - 2017-08-16
### Changed
- No longer watermarking compiled API Blueprint `api.apib` files with a link to Mill and the time of generation.

## [2.5.4] - 2017-07-25
### Fixed
- Fixed some PHP 5.4 compatibility issues with the new changelog work.

## [2.5.3] - 2017-07-24
### Changed
- JSON formatted changelogs now have more relevant data attributes in their HTML elements.

## [2.5.2] - 2017-07-17
### Changed
- Better grouping of like-resource grouped items in generated changelogs.

## [2.5.1] - 2017-07-11
### Removed
- Removed the newly added in 2.5.0 dependency, `doctrine/collections`, because it requires >=PHP 5.6, and Mill 2.x still needs to support PHP 5.4.

## [2.5.0] - 2017-07-10
### Added
- Added a new `releaseDate` attribute, and description content, to `versions` configs. This data is added into generated changelogs.

## [2.4.0] - 2017-06-29
### Changed
- JSON changelog generation content now has important pieces of information wrapped in styleable HTML elements.

## [2.3.2] - 2017-06-28
### Fixed
- URI aliases are now filtered according to your filter settings when generating documentation.

## [2.3.1] - 2017-06-27
### Changed
- Flipped the logic on capability-locked documentation in the generator from an empty array implying no capabilities, null representing all.

## [2.3.0] - 2017-06-27
### Added
- Added support for generating visibility-specific changelogs. [#97](https://github.com/vimeo/mill/issues/97)

## [2.2.2] - 2017-06-15
### Added
- Added resource action descriptions into generated API Blueprint files.

## [2.2.1] - 2017-06-15
### Changed
- No longer adding `Content-Type` header sections into generated API Blueprint files.

## [2.2.0] - 2017-06-12
### Added
- A new `changelog` command that will generate a Markdown-representation changelog from your API docs. [#10](https://github.com/vimeo/mill/issues/10)

## [2.1.1] - 2017-05-25
### Fixed
- Fixed a bug where `@api-scope` annotations with `@api-data` weren't cascading into dot-notation children. [#92](https://github.com/vimeo/mill/pull/92)

## [2.1.0] - 2017-05-22
### Added
- Added support for `@api-scope` annotations alongside `@api-data`. [#90](https://github.com/vimeo/mill/issues/90)

## [2.0.6] - 2017-05-19
### Fixed
- Representation data with an `enum` subtype wasn't getting any present enum member values compiled into API Blueprint files. [#85](https://github.com/vimeo/mill/issues/85)

## [2.0.5] - 2017-05-19
### Changed
- The first Item in a MSON members is now selected as the available sample data if no sample data was present. [#81](https://github.com/vimeo/mill/issues/81)

### Fixed
- `@api-version` and `@api-capability` annotations now carry down into their dot-notation child elements. [#80](https://github.com/vimeo/mill/issues/80)
- Dot-notation elements, with a depth of at least 1, and didn't have any documented parents of siblings, weren't getting compiled into API Blueprint files. [#82](https://github.com/vimeo/mill/issues/82)

## [2.0.4] - 2017-05-17
### Added
- Added support for `date` types in MSON-supported annotations.

## [2.0.3] - 2017-05-16
### Fixed
Fixed a PHP 5.4 incompatibility with a class constant being broken up on multiple lines with string concatenation.

## [2.0.2] - 2017-05-12
### Added
- You can now have multiple `@api-data` annotations within the same docblock. [#79](https://github.com/vimeo/mill/pull/79)

## [2.0.1] - 2017-05-12
### Changed
- API Blueprint data entries are now suffixed with a colon if they have sample data present.

## [2.0.0] - 2017-05-10
### Added
- `@api-see` annotations now have support for static/self targets. [#76](https://github.com/vimeo/mill/pull/76)
- Added some API Blueprint validation into the build process. [#64](https://github.com/vimeo/mill/issues/64)

### Changed
- Introduction of a new Mill-flavored MSON syntax for parameters, URI segments, and representation data. [#42](https://github.com/vimeo/mill/issues/42)
    - Replacing representation usage of `@api-label`, `@api-field`, `@api-type`, `@api-subtype`, `@api-options`, and `@api-capability` with a new `@api-data` annotation.

## [1.6.8] - 2017-05-05
### Added
- Support for a new `alias` decorator on `@api-uri` annotations. [#71](https://github.com/vimeo/mill/pull/71)

## [1.6.7] - 2017-04-21
### Added
- `@api-contentType` annotations now have support for versioning. [#65](https://github.com/vimeo/mill/pull/65)

## [1.6.6] - 2017-04-20
### Fixed
- API Blueprint headers need to be indented 3x. [#60](https://github.com/vimeo/mill/pull/60)

## [1.6.5] - 2017-04-20
### Added
- `@api-uriSegment` annotations with enum values now have those values represented in generated API Blueprint files. [#59](https://github.com/vimeo/mill/pull/59)

## [1.6.4] - 2017-04-20
### Added
- Generated API Blueprint files now contain `Content-Type` headers from the `@api-contentType` annotation. [#57](https://github.com/vimeo/mill/pull/57)

## [1.6.3] - 2017-04-12
### Fixed
- Restricted the `@api-param` enum value regex so it would no longer attempt to match any Markdown in parameter descriptions  [#55](https://github.com/vimeo/mill/pull/55)

## [1.6.2] - 2017-03-24
### Fixed
- Trailing whitespace on any enum value declarations has been trimmed during parsing. [#52](https://github.com/vimeo/mill/pull/52)

## [1.6.1] - 2017-03-20
### Changed
- Parameter and representation enums are now alphabetized during parsing. [#49](https://github.com/vimeo/mill/pull/49)

## [1.6.0] - 2017-03-20
### Added
- API Blueprint files are now compiled with data structures. [#47](https://github.com/vimeo/mill/pull/47)
- Added a new `generators` config that lets you exclude documentation groups from being compiled into API Blueprint files. [#48](https://github.com/vimeo/mill/pull/48)

### Changed
- API Blueprint files are now compiled into three separate files and directories. [#47](https://github.com/vimeo/mill/pull/47)
    - `resources/` - Previous resource groups.
    - `representations/` - Data representations.
    - `api.apib` - Combined file of groups and representations.

## [1.5.0] - 2017-03-14
### Changed
- `@api-param` types are now strict to what we support for API Blueprint compilation. [#45](https://github.com/vimeo/mill/pull/45)

## [1.4.0] - 2017-03-06
### Added
- Better code coverage. [#32](https://github.com/vimeo/mill/pull/32)
- Tests to verify that SemVer patch versioning works with the generator. [#36](https://github.com/vimeo/mill/pull/36)

### Changed
- All resource parameters are now alphabetized during parsing. [#38](https://github.com/vimeo/mill/pull/38)

### Fixed
- Versions attached to `@api-see` annotations now carry down through to anything found from that lookup. [#33](https://github.com/vimeo/mill/pull/33)

## [1.3.0] - 2017-02-07
### Added
- Contributing guidelines
- Rewrote the versioning backend to have 100% support for SemVer constraints. [#27](https://github.com/vimeo/mill/pull/27)

## [1.2.0] - 2017-02-04
### Changed
- Merged the `exclude` and `ignores` config systems into a single `excludes` declaration. [#24](https://github.com/vimeo/mill/pull/24)
- `@api-return` annotations no longer require a representation. [#25](https://github.com/vimeo/mill/pull/25)

## [1.1.0] - 2017-02-03
### Fixed
- Representations are now properly versioned in compiled API Blueprint files. [#23](https://github.com/vimeo/mill/pull/23)

## 1.0.0 - 2017-01-31
### Added
- First release!

[5.1.7]: https://github.com/vimeo/mill/compare/5.1.6...5.1.7
[5.1.6]: https://github.com/vimeo/mill/compare/5.1.5...5.1.6
[5.1.5]: https://github.com/vimeo/mill/compare/5.1.4...5.1.5
[5.1.4]: https://github.com/vimeo/mill/compare/5.1.3...5.1.4
[5.1.3]: https://github.com/vimeo/mill/compare/5.1.2...5.1.3
[5.1.2]: https://github.com/vimeo/mill/compare/5.1.1...5.1.2
[5.1.1]: https://github.com/vimeo/mill/compare/5.1.0...5.1.1
[5.1.0]: https://github.com/vimeo/mill/compare/5.0.2...5.1.0
[5.0.2]: https://github.com/vimeo/mill/compare/5.0.1...5.0.2
[5.0.1]: https://github.com/vimeo/mill/compare/5.0.0...5.0.1
[5.0.0]: https://github.com/vimeo/mill/compare/4.2.13...5.0.0
[4.2.13]: https://github.com/vimeo/mill/compare/4.2.12...4.2.13
[4.2.12]: https://github.com/vimeo/mill/compare/4.2.11...4.2.12
[4.2.11]: https://github.com/vimeo/mill/compare/4.2.10...4.2.11
[4.2.10]: https://github.com/vimeo/mill/compare/4.2.9...4.2.10
[4.2.9]: https://github.com/vimeo/mill/compare/4.2.8...4.2.9
[4.2.8]: https://github.com/vimeo/mill/compare/4.2.7...4.2.8
[4.2.7]: https://github.com/vimeo/mill/compare/4.2.6...4.2.7
[4.2.6]: https://github.com/vimeo/mill/compare/4.2.5...4.2.6
[4.2.5]: https://github.com/vimeo/mill/compare/4.2.4...4.2.5
[4.2.4]: https://github.com/vimeo/mill/compare/4.2.3...4.2.4
[4.2.3]: https://github.com/vimeo/mill/compare/4.2.2...4.2.3
[4.2.2]: https://github.com/vimeo/mill/compare/4.2.1...4.2.2
[4.2.1]: https://github.com/vimeo/mill/compare/4.2.0...4.2.1
[4.2.0]: https://github.com/vimeo/mill/compare/4.1.0...4.2.0
[4.1.0]: https://github.com/vimeo/mill/compare/4.0.0...4.1.0
[4.0.0]: https://github.com/vimeo/mill/compare/3.0.2...4.0.0
[3.0.2]: https://github.com/vimeo/mill/compare/3.0.1...3.0.2
[3.0.1]: https://github.com/vimeo/mill/compare/3.0.0...3.0.1
[3.0.0]: https://github.com/vimeo/mill/compare/2.6.4...3.0.0
[2.6.4]: https://github.com/vimeo/mill/compare/2.6.3...2.6.4
[2.6.3]: https://github.com/vimeo/mill/compare/2.6.2...2.6.3
[2.6.2]: https://github.com/vimeo/mill/compare/2.6.1...2.6.2
[2.6.1]: https://github.com/vimeo/mill/compare/2.6.0...2.6.1
[2.6.0]: https://github.com/vimeo/mill/compare/2.5.6...2.6.0
[2.5.6]: https://github.com/vimeo/mill/compare/2.5.5...2.5.6
[2.5.5]: https://github.com/vimeo/mill/compare/2.5.4...2.5.5
[2.5.4]: https://github.com/vimeo/mill/compare/2.5.3...2.5.4
[2.5.3]: https://github.com/vimeo/mill/compare/2.5.2...2.5.3
[2.5.2]: https://github.com/vimeo/mill/compare/2.5.1...2.5.2
[2.5.1]: https://github.com/vimeo/mill/compare/2.5.0...2.5.1
[2.5.0]: https://github.com/vimeo/mill/compare/2.4.0...2.5.0
[2.4.0]: https://github.com/vimeo/mill/compare/2.3.2...2.4.0
[2.3.2]: https://github.com/vimeo/mill/compare/2.3.1...2.3.2
[2.3.1]: https://github.com/vimeo/mill/compare/2.3.0...2.3.1
[2.3.0]: https://github.com/vimeo/mill/compare/2.2.2...2.3.0
[2.2.2]: https://github.com/vimeo/mill/compare/2.2.1...2.2.2
[2.2.1]: https://github.com/vimeo/mill/compare/2.2.0...2.2.1
[2.2.0]: https://github.com/vimeo/mill/compare/2.1.1...2.2.0
[2.1.1]: https://github.com/vimeo/mill/compare/2.1.0...2.1.1
[2.1.0]: https://github.com/vimeo/mill/compare/2.0.6...2.1.0
[2.0.6]: https://github.com/vimeo/mill/compare/2.0.5...2.0.6
[2.0.5]: https://github.com/vimeo/mill/compare/2.0.4...2.0.5
[2.0.4]: https://github.com/vimeo/mill/compare/2.0.3...2.0.4
[2.0.3]: https://github.com/vimeo/mill/compare/2.0.2...2.0.3
[2.0.2]: https://github.com/vimeo/mill/compare/2.0.1...2.0.2
[2.0.1]: https://github.com/vimeo/mill/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/vimeo/mill/compare/1.6.8...2.0.0
[1.6.8]: https://github.com/vimeo/mill/compare/1.6.7...1.6.8
[1.6.7]: https://github.com/vimeo/mill/compare/1.6.6...1.6.7
[1.6.6]: https://github.com/vimeo/mill/compare/1.6.5...1.6.6
[1.6.5]: https://github.com/vimeo/mill/compare/1.6.4...1.6.5
[1.6.4]: https://github.com/vimeo/mill/compare/1.6.3...1.6.4
[1.6.3]: https://github.com/vimeo/mill/compare/1.6.2...1.6.3
[1.6.2]: https://github.com/vimeo/mill/compare/1.6.1...1.6.2
[1.6.1]: https://github.com/vimeo/mill/compare/1.6.0...1.6.1
[1.6.0]: https://github.com/vimeo/mill/compare/1.5.0...1.6.0
[1.5.0]: https://github.com/vimeo/mill/compare/1.4.0...1.5.0
[1.4.0]: https://github.com/vimeo/mill/compare/1.3.0...1.4.0
[1.3.0]: https://github.com/vimeo/mill/compare/1.2.0...1.3.0
[1.2.0]: https://github.com/vimeo/mill/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/vimeo/mill/compare/1.0.0...1.1.0
