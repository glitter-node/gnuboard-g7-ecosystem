# Changelog

All notable changes to this module will be documented in this file.

The format is based on Keep a Changelog.

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
