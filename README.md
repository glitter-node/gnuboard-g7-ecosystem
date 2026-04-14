# gnuboard-g7-ecosystem

A monorepo for managing, developing, and distributing Gnuboard7 modules, plugins, themes, and service compositions as portable folder-based units.

## Overview

This repository organizes the Gnuboard7 ecosystem into clearly separated layers:

- Modules for feature-level extensions
- Plugins for integrations and auxiliary behavior
- Themes for UI and layout rendering
- Distributions for complete service compositions

Each component is designed to be reusable, portable, and installable without modifying the Gnuboard7 core.

## Repository Structure

```
gnuboard_g7_ecosystem/
├── modules/
│   ├── _bundled/
│   └── _develop/
├── plugins/
├── themes/
├── distributions/
├── docs/
└── tools/
```

## Modules

The modules directory contains all Gnuboard7 modules.

- `_develop`: modules under active development
- `_bundled`: modules ready for distribution

A bundled module must:

- include module.json and module.php
- be installable by copying into modules/_bundled
- not require modification of Gnuboard7 core
- maintain runtime independence

## Plugins

The plugins directory contains optional integrations and supporting features such as notifications, messaging, or external service bindings.

Plugins are designed to remain loosely coupled with modules.

## Themes

The themes directory defines user and admin interfaces.

Each theme is responsible for:

- layout rendering
- route mapping (routes.json)
- exposing module functionality to UI

## Distributions

The distributions directory represents complete service compositions.

A distribution defines:

- which modules are used
- which plugins are attached
- which theme is applied

This enables reproducible system setups.

## Tools

The tools directory contains scripts and utilities for:

- packaging modules
- preparing releases
- automating repetitive tasks

## Documentation

The docs directory contains:

- module specifications
- plugin conventions
- architectural decisions

## Installation Model

All components follow a folder-based installation model.

A bundled module can be installed by copying it into the target project:

cp -r <module-name> modules/_bundled/

Then install and activate it via the admin interface.

## Design Principles

- No modification of Gnuboard7 core
- Folder is the unit of deployment
- Modules must be self-contained
- Clear separation between runtime roles
- Packaging stability over convenience

## Distribution Flow

_develop → _bundled → GitHub release → user installation

## Target

- Gnuboard7 system operators
- Developers building modular architectures
- Teams creating reusable CMS-based systems

## Status

The ecosystem structure is defined and operational.  
Modules are being standardized into portable bundled units.  
Distribution and release workflow is being formalized.

## License

Each module or component may define its own license.
