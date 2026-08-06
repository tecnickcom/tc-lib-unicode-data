# tc-lib-unicode-data

> Unicode data tables and constants used by the Tecnick text stack.

[![Latest Stable Version](https://poser.pugx.org/tecnickcom/tc-lib-unicode-data/version)](https://packagist.org/packages/tecnickcom/tc-lib-unicode-data)
[![Build](https://github.com/tecnickcom/tc-lib-unicode-data/actions/workflows/check.yml/badge.svg)](https://github.com/tecnickcom/tc-lib-unicode-data/actions/workflows/check.yml)
[![Coverage](https://codecov.io/gh/tecnickcom/tc-lib-unicode-data/graph/badge.svg?token=12SAG9XRFK)](https://codecov.io/gh/tecnickcom/tc-lib-unicode-data)
[![License](https://poser.pugx.org/tecnickcom/tc-lib-unicode-data/license)](https://packagist.org/packages/tecnickcom/tc-lib-unicode-data)
[![Downloads](https://poser.pugx.org/tecnickcom/tc-lib-unicode-data/downloads)](https://packagist.org/packages/tecnickcom/tc-lib-unicode-data)

[![Sponsor on GitHub](https://img.shields.io/badge/sponsor-github-EA4AAA.svg?logo=githubsponsors&logoColor=white)](https://github.com/sponsors/tecnickcom)

> 💖 Part of the [tc-lib-pdf / TCPDF](https://github.com/tecnickcom/tc-lib-pdf) ecosystem (100M+ installs). [Sponsor its maintenance →](https://github.com/sponsors/tecnickcom)

---

## Overview

`tc-lib-unicode-data` is a data-centric package that provides Unicode lookup tables, mappings, and constants consumed by `tc-lib-unicode` and related libraries.

It externalizes large Unicode datasets into a dedicated package so runtime libraries can stay focused on algorithms instead of data distribution. Versioned data updates also become easier to manage and review as Unicode standards evolve.

| | |
|---|---|
| **Namespace** | `\Com\Tecnick\Unicode\Data` |
| **Author** | Nicola Asuni <info@tecnick.com> |
| **License** | [GNU LGPL v3](https://www.gnu.org/copyleft/lesser.html) - see [LICENSE](LICENSE) |
| **API docs** | <https://tcpdf.org/docs/srcdoc/tc-lib-unicode-data> |
| **Packagist** | <https://packagist.org/packages/tecnickcom/tc-lib-unicode-data> |

---

## Features

### Data Coverage
- Unicode property and identity constants
- Script/category mapping data
- Bracket, mirroring, and shaping-related tables

### Integration Role
- Runtime dependency for higher-level Unicode processing
- Pure data distribution, no heavy runtime logic
- Deterministic, versioned updates

---

## Unicode data

`Arabic`, `Bracket`, `Mirror`, `Pattern` and `Type` are generated from the Unicode Character Database; `Type::UNICODE_VERSION` reports the version they derive from.

| Class | UCD source | Content |
|---|---|---|
| `Type` | `extracted/DerivedBidiClass.txt` | Bidi_Class of every code point (UAX #9) |
| `Pattern` | `extracted/DerivedBidiClass.txt` | Regular expressions matching right-to-left and Arabic text |
| `Mirror` | `BidiMirroring.txt` | Bidi_Mirroring_Glyph values used by rule L4 |
| `Bracket` | `BidiBrackets.txt` | Paired brackets used by rule N0 |
| `Arabic` | `ArabicShaping.txt`, `UnicodeData.txt` | Joining types, presentation forms and ligatures |

`Type::UNI` only lists the code points whose Bidi_Class is not `L`; `Type::getType()` resolves any code point, including the blocks whose unassigned code points default to `R`, `AL` or `ET`.

```php
\Com\Tecnick\Unicode\Data\Type::getType(0x05D0);          // 'R'
\Com\Tecnick\Unicode\Data\Type::getBidiClass(0x0660);     // BidiClass::AN
\Com\Tecnick\Unicode\Data\Arabic::getJoiningType(0x0628); // 'D'
```

To rebuild the tables from a newer UCD release:

```bash
make gendata UCDVERSION=17.0.0
make qa
```

---

## Upgrading from 2.x

- `Type::UNI` no longer contains the code points whose Bidi_Class is `L`: read it through `Type::getType()` instead of `Type::UNI[$ord] ?? $fallback`.
- `Type::getBidiClass()` returns `BidiClass::L` for the code points that are not listed, and `null` only for the explicit formatting codes (LRE, LRO, RLE, RLO, PDF, LRI, RLI, FSI, PDI).
- `Pattern::RTL` and `Pattern::ARABIC` are code point based (`u` modifier) and require a valid UTF-8 subject.
- `Mirror::UNI` follows `BidiMirroring.txt`: the quotation marks U+2018, U+2019, U+201C, U+201D, U+301D and U+301E are no longer mirrored.
- `Arabic::SUBSTITUTE` rows always hold the four `[isolated, final, initial, medial]` forms, repeating the isolated and final ones where the letter has no initial or medial form.
- `Arabic::END` is derived from the Joining_Type property and now lists every character that does not join to the following one.
- `Arabic::JOINING` and `Arabic::getJoiningType()` expose the Joining_Type property.

---

## Requirements

- PHP 8.2 or later
- Composer

---

## Installation

```bash
composer require tecnickcom/tc-lib-unicode-data
```

---

## Quick Start

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

echo md5(\Com\Tecnick\Unicode\Data\Identity::CIDHMAP);
```

---

## Development

```bash
make deps
make help
make qa
```

---

## Packaging

```bash
make rpm
make deb
```

For system packages, bootstrap with:

```php
require_once '/usr/share/php/Com/Tecnick/Unicode/Data/autoload.php';
```

---

## Contributing

Contributions are welcome. Please review [CONTRIBUTING.md](CONTRIBUTING.md), [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md), and [SECURITY.md](SECURITY.md).

