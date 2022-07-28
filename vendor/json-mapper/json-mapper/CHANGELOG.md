# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.12.0] - 2022-03-31
### Fixed
- Cannot lookup property of namespace in parent class [PR#135](https://github.com/JsonMapper/JsonMapper/pull/135) Thanks to [jg-development](https://github.com/jg-development) for reporting the issue 

## [2.11.1] - 2022-03-08
### Fixed
- Flex myclabs/php-enum constraints [PR#132](https://github.com/JsonMapper/JsonMapper/pull/132) Thanks to [Christopher Reimer](https://github.com/CReimer) for reporting the issue

## [2.11.0] - 2022-02-09
### Changed
- Merging of the property map is optimised with an early return if left side and right side are equal [PR#128](https://github.com/JsonMapper/JsonMapper/pull/128)
### Fixed
- Flex psr/log constraints [PR#129](https://github.com/JsonMapper/JsonMapper/pull/129)

## [2.10.0] - 2022-01-16
### Added
- Support was added for strict scalar casting [PR#119](https://github.com/JsonMapper/JsonMapper/pull/119) Thanks to [template-provider](https://github.com/template-provider) for reporting the issue
- All **Map** functions now return the mapped object(s) and uses [Psalm](https://psalm.dev) to assist with autocompletion. [PR#122](https://github.com/JsonMapper/JsonMapper/pull/122)
### Fixed
- Replace duplicates in middleware with object wrapper calls. [PR#123](https://github.com/JsonMapper/JsonMapper/pull/123)
- Correct code style issues. [PR#124](https://github.com/JsonMapper/JsonMapper/pull/124)
- Return empty array for union type with an array type when value is an empty array. [PR#126](https://github.com/JsonMapper/JsonMapper/pull/126) Thanks to [template-provider](https://github.com/template-provider) for reporting the issue

## [2.9.1] - 2021-11-12
### Fixed
- Namespace resolving improved to include imports from parent classes [PR#117](https://github.com/JsonMapper/JsonMapper/pull/117) Thanks to [template-provider](https://github.com/template-provider) for reporting the issue

## [2.9.0] - 2021-11-09
### Added
- The value transformation middleware was added to apply a callback to the values of the json object [PR#111](https://github.com/JsonMapper/JsonMapper/pull/111) Thanks to [Philipp Dahse](https://github.com/dahse89)
- Introduce psalm annotations [PR#110](https://github.com/JsonMapper/JsonMapper/pull/110)
### Fixed
- Namespace resolving was strengthened to avoid partial matches and now also includes support for using `alias` in `use` statements [PR#112](https://github.com/JsonMapper/JsonMapper/pull/112) Thanks to [Christopher Reimer](https://github.com/CReimer)

## [2.8.0] - 2021-10-05
### Added
- Support for PHP 8.1 Enum [PR#105](https://github.com/JsonMapper/JsonMapper/pull/105)

## [2.7.0] - 2021-08-31
### Fixed
- Correctly map types for array type with reused internal classname withing same namespace [PR#103](https://github.com/JsonMapper/JsonMapper/pull/103)
### Changed
- Invoke PHP native functions with fq namespace to improve speed. [PR#100](https://github.com/JsonMapper/JsonMapper/pull/100)

## [2.6.0] - 2021-07-15
### Added 
- Support PHP 7.1 [PR#97](https://github.com/JsonMapper/JsonMapper/pull/97)

## [2.5.1] - 2021-07-06
### Fixed
- Preserve cache keys uniqueness within a single cache instance [PR#95](https://github.com/JsonMapper/JsonMapper/pull/95)

## [2.5.0] - 2021-05-17
### Added
- Map to stdClass was added to allow for generic objects [PR#89](https://github.com/JsonMapper/JsonMapper/pull/89)
- Map interfaces and abstract classes using factories [PR#84](https://github.com/JsonMapper/JsonMapper/pull/84)
- Suggestions where added to the composer.json file to inform about the laravel and symfony libs we have available. [PR#90](https://github.com/JsonMapper/JsonMapper/pull/90)
### Changed
- Split integration test into smaller test cases divided by individual features [PR#91](https://github.com/JsonMapper/JsonMapper/pull/91)

## [2.4.1] - 2021-05-07
### Fixed
- Namespace resolver merging replaces property instead of merging old and new property types [PR#86](https://github.com/JsonMapper/JsonMapper/pull/86)

## [2.4.0] - 2021-04-15
### Added
- Caching to the namespace resolver was added to reduce time and memory footprint [PR#82](https://github.com/JsonMapper/JsonMapper/pull/82)

## [2.3.1] - 2021-03-30
### Fixed
- Property identified by the same name is merged with the previous property [PR#79](https://github.com/JsonMapper/JsonMapper/pull/79)

## [2.3.0] - 2021-03-18
### Added
- JsonMapperBuilder offers a fluent interface for building a JsonMapper instance [PR#76](https://github.com/JsonMapper/JsonMapper/pull/76)

## [2.2.0] - 2021-02-04
### Added
- Support for renaming JSON properties before mapping values onto an object [PR#75](https://github.com/JsonMapper/JsonMapper/pull/75)

## [2.1.0] - 2021-01-28
### Added
- Support variadic setter function. [PR#68](https://github.com/JsonMapper/JsonMapper/pull/68)
### Fixed
- Include PHP 8.0 in the build matrix. [PR#70](https://github.com/JsonMapper/JsonMapper/pull/70)
- Complete switch to GitHub Actions and remove Travis. [PR#73](https://github.com/JsonMapper/JsonMapper/pull/73)
- Resolve code style and static analysis issues. [PR#71](https://github.com/JsonMapper/JsonMapper/pull/71)

## [2.0.0] - 2021-01-07
### Changed
- Improve the test using PropertyAssertionChain. [PR#62](https://github.com/JsonMapper/JsonMapper/pull/62)
- Added support for union types in JsonMapper. [PR#65](https://github.com/JsonMapper/JsonMapper/pull/65)

## [1.4.2] - 2020-10-30
## Fixed
- Fix null array support in DocBlock middleware. [PR#60](https://github.com/JsonMapper/JsonMapper/pull/60)

## [1.4.1] - 2020-10-27
## Fixed
- Fix null values provided from JSON. [PR#59](https://github.com/JsonMapper/JsonMapper/pull/59)

## [1.4.0] - 2020-10-22
### Added
- Add support for mapping from strings [PR#46](https://github.com/JsonMapper/JsonMapper/pull/46)
- Add support for class factories [PR#54](https://github.com/JsonMapper/JsonMapper/pull/54)
- Add Attributes middleware [PR#55](https://github.com/JsonMapper/JsonMapper/pull/55)

## [1.3.0] - 2020-08-11
### Added
- Add support for mixed type [PR#39](https://github.com/JsonMapper/JsonMapper/pull/39)
### Changed
- Improved internal representation of scalar types, introducing ScalarType Enum class. [PR#34](https://github.com/JsonMapper/JsonMapper/pull/34)
## Fixed
- Fix mapping to a class from the same namespace when using PHP 7.4 namespace is prefixed twice. [PR#41](https://github.com/JsonMapper/JsonMapper/pull/41)

## [1.2.0] - 2020-07-12
### Added
- Introduce pop, unshift, shift, remove, removeByName methods to the JsonMapperInterface [PR#32](https://github.com/JsonMapper/JsonMapper/pull/32)
### Fixed
- Resolved several issues found by PHPStan [PR#29](https://github.com/JsonMapper/JsonMapper/pull/29)
- Properties marked as array are casted to enable object to array mapping [PR#36](https://github.com/JsonMapper/JsonMapper/pull/36)
### Changed
- Reduced a single used helper splitting into the core and into the doc block middleware. [PR#30](https://github.com/JsonMapper/JsonMapper/pull/30)

## [1.1.0] - 2020-05-29
### Added 
- Support for arrays using square bracket notation (e.g. User[]) in DocBlockAnnotations middleware. (PR#27/#28)

## [1.0.1] - 2020-05-04
### Fixed
- Case conversion removing attribute when replacement key is same as the original key

## [1.0.0] - 2020-04-23
### Added
- New Debugger middleware to help debug the in between middleware
- Caching support to the DocBlockAnnotations and TypedProperties middleware

## [0.3.0] - 2020-04-13
### Added 
- New FinalCallback middleware to invoke a final callback when mapping is completed.
- New CaseConversion middleware to handle difference between text notation in JSON and object

## [0.2.1] - 2020-03-25
### Fixed
- Correct badge urls in readme

## [0.2.0] - 2020-03-25
### Changed
- Changed top level namespace 

## [0.1.0] - 2020-03-25
### Added
- Factory for easy creation of new JsonMapper instance 
### Changed
- Replaced strategies with middleware to allow chaining of multiple middleware to increase configuration
- Readme was updated to reflect the usage and customizing of JsonMapper
- Updated license to MIT

## [0.0.2] - 2020-03-22
### Added
- Support custom classes with recursion
- Support for custom classes with imported namespace
- Support to map an array of objects
### Fixed
- Fixed missing coveralls dependency
- Cleanup strategies from duplication

## [0.0.1] - 2020-03-15
### Added
- Add PHP 7.4 typed properties based strategy
- Add DocBlock based strategy
- Add support for DateTime types
- Add typecasting
- Add value setting logic based on strategy 
