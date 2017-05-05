# Changelog
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
