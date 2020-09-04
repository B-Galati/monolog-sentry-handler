# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased](https://github.com/B-Galati/monolog-sentry-handler/compare/1.2.2...master)

## [1.2.2](https://github.com/B-Galati/monolog-sentry-handler/compare/1.2.1...1.2.2) - 2020-09-04

- 📝 Add filter deprecation logs to the doc - [@B-Galati](https://github.com/B-Galati) ([e1c70a](https://github.com/B-Galati/monolog-sentry-handler/commit/e1c70a3da87f44b923173becf7bec59f9756696a))
- 🔧 Remove snapshot usage for php7.4 in travis - [@B-Galati](https://github.com/B-Galati) ([fb9e5d](https://github.com/B-Galati/monolog-sentry-handler/commit/fb9e5d773de6076d9ee448b9a8a9db355b80aa59))
- 📝 Update Symfony guide - [@B-Galati](https://github.com/B-Galati) ([727706](https://github.com/B-Galati/monolog-sentry-handler/commit/727706b952bc1119a0b5da1ec12ff104682f386a))
- 🔧 Use CS fixer in Travis instead of PrettyCI - [@B-Galati](https://github.com/B-Galati) ([5a4551](https://github.com/B-Galati/monolog-sentry-handler/commit/5a45512f4da9d47a972225dedc493ad27d6434fe))
- 🔧 Update PHPStan - [@B-Galati](https://github.com/B-Galati) ([183b84](https://github.com/B-Galati/monolog-sentry-handler/commit/183b8404bfd2171669f29a3f1c3420ef870b9f81))
- 🔧 Bump minimal version of Sentry SDK to 2.3.2 - [@B-Galati](https://github.com/B-Galati) ([808ce2](https://github.com/B-Galati/monolog-sentry-handler/commit/808ce2c5cd011593e26e0050b7346377ce767b1f))
- 🔧 Fix Sentry deprecated in tests code - [@B-Galati](https://github.com/B-Galati) ([f94ac9](https://github.com/B-Galati/monolog-sentry-handler/commit/f94ac97c1aedcb221f80624737f007ac42231ac4))
- 🔧 Add PHP nightly to the CI - [@B-Galati](https://github.com/B-Galati) ([ce7acb](https://github.com/B-Galati/monolog-sentry-handler/commit/ce7acbbb0c698c62d83d11bd7af42c92e66abe41))
- 🔧 Move SYMFONY_DEPRECATIONS_HELPER to phpunit.xml.dist - [@B-Galati](https://github.com/B-Galati) ([864569](https://github.com/B-Galati/monolog-sentry-handler/commit/8645697b9ff93dfc5a44fd0076021501c2837a10))
- 🔧 Use dev stability deps with nightly build - [@B-Galati](https://github.com/B-Galati) ([7d6957](https://github.com/B-Galati/monolog-sentry-handler/commit/7d6957601451c8b2fbb4c912c3c542b2bdf8c4a1))
- 🐛 fix #21 - Filtering records should not crash the handler - [@B-Galati](https://github.com/B-Galati) ([323c11](https://github.com/B-Galati/monolog-sentry-handler/commit/323c11ebb0f1e1ab6a4005c89eff8330b2f39177))
- ✨ Allow PHP 8 - [@B-Galati](https://github.com/B-Galati) ([30cfe7](https://github.com/B-Galati/monolog-sentry-handler/commit/30cfe70a123b5e8ee38976f6a64aff56f06eba81))

## [1.2.1](https://github.com/B-Galati/monolog-sentry-handler/compare/1.2.0...1.2.1) - 2019-12-13

### Fixed
- Support initial value being null on array_reduce (#16) - [@jandeschuttere](https://github.com/jandeschuttere) ([095d6d](https://github.com/B-Galati/monolog-sentry-handler/commit/095d6d57e7feaeeb30498f8c4a7fec963b4fde84))

## [1.2.0](https://github.com/B-Galati/monolog-sentry-handler/compare/1.1.0...1.2.0) - 2019-11-06
### Added
- Add extension points (fix #3) (#12)
  >See the [doc](doc/extension-points.md)!
- Re-add .gitattributes as it makes dist lighter (#11)

### Changed
- Suggest Symfony HttplugClient in symfony guide (#13)
- Use Fatal instead of critical level for breadcrumbs (Critical is deprecated) (fix #9) (#11)

### Fixed
- Flush messages on Handler write (fix #10) (#11)
- Bump sentry SDK minimum version to 2.2.3 (#11)

## [1.1.0](https://github.com/B-Galati/monolog-sentry-handler/compare/1.0.0...1.1.0) - 2019-09-26
### Added
- Monolog v2 compatibility (fix #4)
- Bump dependency to "sentry/sentry:^2.2.1" (fix #6)
- Document how to use another http client than curl HTTPlug (fix #7)

### Changed
- Update doc

### Removed
- Remove .gitattributes

## [1.0.0](https://github.com/B-Galati/monolog-sentry-handler/compare/acf546c...1.0.0) - 2019-08-17
### Added
- First version of `SentryHandler`
