# Changelog

All notable changes to this module will be documented in this file.

The format is based on Keep a Changelog.

## [0.2.0] - 2026-04-14

### Added
- bundled packaging scaffold for distribution
- build scaffold with vite.config.ts, resources/js, and resources/css
- dist placeholder files for packaged module shape
- no-op install seeder scaffold
- no-op upgrade scaffold
- ecosystem-level distribution readiness via `_bundled` module placement

### Changed
- finalized glitter-reservation as a bundled Gnuboard7 module
- aligned module structure with bundled module conventions
- completed packaging layer without altering runtime behavior
- confirmed compatibility with copy-based installation into `modules/_bundled`

### Notes
- this release marks transition from portable module to bundled distribution unit
- module is now ready for GitHub-based folder-level distribution
- asset pipeline and upgrade scaffolds are intentionally minimal and non-intrusive

## [0.1.0] - 2026-04-14

### Added
- portable install contract documentation for host projects
- release metadata scaffolding for GitHub distribution
- release version tracking files for the portable bundle

### Changed
- finalized the module scope as a portable Gnuboard 7 reservation bundle
- decoupled public and admin page exposure from hardcoded host paths
- normalized the portable migration set to `database/migrations/`
- aligned bundle metadata and release documentation for distribution

### Removed
- removed `student_grade` from the portable bundle target
- removed dead listener, dead request, and dead enum files from the portable target

### Notes
- excluded historical migrations from portable installs via `database/migrations_excluded/`
- completed the host integration and install contract for template route mapping, initial data, and optional notification binding
