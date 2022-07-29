# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased](https://github.com/B-Galati/monolog-sentry-handler/compare/2.0.0...main)

## [2.0.0](https://github.com/B-Galati/monolog-sentry-handler/compare/1.2.2...2.0.0) - 2022-07-29

**New major release!**

See the [upgrade guide](UPGRADE-2.x.md) to know what are the BC breaks.

New [example repository](https://github.com/B-Galati/monolog-sentry-handler-example) to quickly play with the handler if needed.

### Changes

-   ✨ Support for Sentry SDK ^3.1 - [@Wirone](https://github.com/Wirone) ([398ee2](https://github.com/B-Galati/monolog-sentry-handler/commit/398ee2abcbe3487741e9540fd39a00b66cc72986))
-   ✨ Support Monolog 3 - [@B-Galati](https://github.com/B-Galati) ([f2a708](https://github.com/B-Galati/monolog-sentry-handler/commit/f2a708f026e33d7498a946c2b570694b615beb67))
-   📝 Update doc for the V2 - [@B-Galati](https://github.com/B-Galati) ([0166a1](https://github.com/B-Galati/monolog-sentry-handler/commit/0166a10d1e08e760088106766f33652ea0fdc75b))

### Internal

-   👷 Remove Travis and init GitHub Actions - [@B-Galati](https://github.com/B-Galati) ([652f58](https://github.com/B-Galati/monolog-sentry-handler/commit/652f583747fc2ed9afc2e2078ec85446f4619394))
-   👷 Migrate to GitHub Actions - [@B-Galati](https://github.com/B-Galati) ([fe1807](https://github.com/B-Galati/monolog-sentry-handler/commit/fe18073f5e7464aeb0b673b8b9a0789b0a523a5d))
-   ⬆️ Upgrade to PHPUnit 9 - [@B-Galati](https://github.com/B-Galati) ([24cb5b](https://github.com/B-Galati/monolog-sentry-handler/commit/24cb5b384de2a6470b43da35e38c12c1ff28d54c))
-   ⬆️ Upgrade to PHP CS Fixer 3 - [@B-Galati](https://github.com/B-Galati) ([3ac9dd](https://github.com/B-Galati/monolog-sentry-handler/commit/3ac9dd98796eccb0c4bf4cd1beb7d92fa3e31977))
-   🔧 Improve Makefile default behavior - [@B-Galati](https://github.com/B-Galati) ([57b0b2](https://github.com/B-Galati/monolog-sentry-handler/commit/57b0b2e1df86d883653991d4b3d843d835caacfb))
-   🔧 Update branch alias to work with main and version 2 - [@B-Galati](https://github.com/B-Galati) ([f285c5](https://github.com/B-Galati/monolog-sentry-handler/commit/f285c553bd66016c0832d5914e2846b9c715a25e))
-   🔧 Update PHPStan and fix the version - [@B-Galati](https://github.com/B-Galati) ([59440a](https://github.com/B-Galati/monolog-sentry-handler/commit/59440a2fb5019ab35e0d0568bcd4b8fb896fa6b0))

## [1.2.2](https://github.com/B-Galati/monolog-sentry-handler/compare/1.2.1...1.2.2) - 2020-09-04

### Changes

-   📝 Add filter deprecation logs to the doc - [@B-Galati](https://github.com/B-Galati) ([e1c70a](https://github.com/B-Galati/monolog-sentry-handler/commit/e1c70a3da87f44b923173becf7bec59f9756696a))
-   📝 Update Symfony guide - [@B-Galati](https://github.com/B-Galati) ([727706](https://github.com/B-Galati/monolog-sentry-handler/commit/727706b952bc1119a0b5da1ec12ff104682f386a))
-   🔧 Bump minimal version of Sentry SDK to 2.3.2 - [@B-Galati](https://github.com/B-Galati) ([808ce2](https://github.com/B-Galati/monolog-sentry-handler/commit/808ce2c5cd011593e26e0050b7346377ce767b1f))
-   🐛 fix #21 - Filtering records should not crash the handler - [@B-Galati](https://github.com/B-Galati) ([323c11](https://github.com/B-Galati/monolog-sentry-handler/commit/323c11ebb0f1e1ab6a4005c89eff8330b2f39177))
-   ✨ Allow PHP 8 - [@B-Galati](https://github.com/B-Galati) ([30cfe7](https://github.com/B-Galati/monolog-sentry-handler/commit/30cfe70a123b5e8ee38976f6a64aff56f06eba81))

### Internal

-   🔧 Update PHPStan - [@B-Galati](https://github.com/B-Galati) ([183b84](https://github.com/B-Galati/monolog-sentry-handler/commit/183b8404bfd2171669f29a3f1c3420ef870b9f81))
-   🔧 Remove snapshot usage for php7.4 in travis - [@B-Galati](https://github.com/B-Galati) ([fb9e5d](https://github.com/B-Galati/monolog-sentry-handler/commit/fb9e5d773de6076d9ee448b9a8a9db355b80aa59))
-   🔧 Use CS fixer in Travis instead of PrettyCI - [@B-Galati](https://github.com/B-Galati) ([5a4551](https://github.com/B-Galati/monolog-sentry-handler/commit/5a45512f4da9d47a972225dedc493ad27d6434fe))
-   🔧 Fix Sentry deprecated in tests code - [@B-Galati](https://github.com/B-Galati) ([f94ac9](https://github.com/B-Galati/monolog-sentry-handler/commit/f94ac97c1aedcb221f80624737f007ac42231ac4))
-   🔧 Add PHP nightly to the CI - [@B-Galati](https://github.com/B-Galati) ([ce7acb](https://github.com/B-Galati/monolog-sentry-handler/commit/ce7acbbb0c698c62d83d11bd7af42c92e66abe41))
-   🔧 Move SYMFONY_DEPRECATIONS_HELPER to phpunit.xml.dist - [@B-Galati](https://github.com/B-Galati) ([864569](https://github.com/B-Galati/monolog-sentry-handler/commit/8645697b9ff93dfc5a44fd0076021501c2837a10))
-   🔧 Use dev stability deps with nightly build - [@B-Galati](https://github.com/B-Galati) ([7d6957](https://github.com/B-Galati/monolog-sentry-handler/commit/7d6957601451c8b2fbb4c912c3c542b2bdf8c4a1))

## [1.2.1](https://github.com/B-Galati/monolog-sentry-handler/compare/1.2.0...1.2.1) - 2019-12-13

### Fixed

-   Support initial value being null on array_reduce (#16) - [@jandeschuttere](https://github.com/jandeschuttere) ([095d6d](https://github.com/B-Galati/monolog-sentry-handler/commit/095d6d57e7feaeeb30498f8c4a7fec963b4fde84))

## [1.2.0](https://github.com/B-Galati/monolog-sentry-handler/compare/1.1.0...1.2.0) - 2019-11-06

### Added

-   Add extension points (fix #3) (#12)
    > See the [doc](doc/extension-points.md)!
-   Re-add .gitattributes as it makes dist lighter (#11)

### Changed

-   Suggest Symfony HttplugClient in symfony guide (#13)
-   Use Fatal instead of critical level for breadcrumbs (Critical is deprecated) (fix #9) (#11)

### Fixed

-   Flush messages on Handler write (fix #10) (#11)
-   Bump sentry SDK minimum version to 2.2.3 (#11)

## [1.1.0](https://github.com/B-Galati/monolog-sentry-handler/compare/1.0.0...1.1.0) - 2019-09-26

### Added

-   Monolog v2 compatibility (fix #4)
-   Bump dependency to "sentry/sentry:^2.2.1" (fix #6)
-   Document how to use another http client than curl HTTPlug (fix #7)

### Changed

-   Update doc

### Removed

-   Remove .gitattributes

## [1.0.0](https://github.com/B-Galati/monolog-sentry-handler/compare/acf546c...1.0.0) - 2019-08-17

### Added

-   First version of `SentryHandler`
