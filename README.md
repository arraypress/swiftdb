# SwiftDB

SwiftDB is an enhanced fork of BerlinDB, expanding upon its robust foundation to provide an even more comprehensive ORM-like experience for WordPress database tables. While maintaining the core principles and elegance of BerlinDB, SwiftDB introduces additional features and improvements to meet modern WordPress development needs.

## Overview

This library provides a structured approach to WordPress database management, offering both the original BerlinDB functionality and new advanced features:

### Original BerlinDB Features
- ORM-like interface for WordPress database tables
- Custom table creation and management
- Structured query building
- Efficient data retrieval and manipulation

### New SwiftDB Enhancements
- Aggregate query support (COUNT, SUM, AVG, etc.)
- Column/row data type casting

### Upcoming SwiftDB Enhancements
- Advanced type system:
    - Strict PHP type hints and return types
    - Automatic type casting for columns and rows
    - Custom type definitions and validation
- Enhanced relationships and JOINs:
    - One-to-one and one-to-many relationships
    - Table relationship definitions
    - JOIN query builder
    - Cross-table type safety
- Modern developer experience:
    - Full PHP 7.4+ type hinting
    - Static analysis support
    - IDE autocompletion friendly
    - Strict type enforcement

## Origin and Evolution

SwiftDB builds upon the excellent foundation of BerlinDB, a project that emerged from WordCamp Europe 2019 and represents the collective wisdom of multiple major WordPress projects including Easy Digital Downloads, Sugar Calendar, and Restrict Content Pro.

The original BerlinDB was created to solve the challenge of custom database management in WordPress, eliminating code duplication and fragmentation across projects. SwiftDB continues this mission while adding modern features and improvements.

## Installation

```bash
composer require arraypress/swiftdb
```

## Credits

SwiftDB is a fork of [BerlinDB](https://github.com/berlindb/core), originally created by Sandhills Development, LLC. The original project represents the cumulative effort of dozens of individuals across multiple projects and continues to inspire this enhanced version.

## License

GPL-2.0-or-later

---

SwiftDB is maintained by [ArrayPress](https://arraypress.com). While this project builds upon BerlinDB's foundation, we aim to push the boundaries of what's possible in WordPress database management.