# M0 — Backend Foundation: Architecture

## Overview

```
MySQL (gdesign)
   ↓  PDO prepared statements
Custom PHP 8.1+ (no framework)
   ↓  REST JSON (/api/v1)
Existing vanilla-JS quote wizard (public/quote/)
   ↓
Existing G DESIGN UI — unchanged
```

M0 scope: foundation + read-only service catalog. No auth, no admin, no request submission, no uploads.

## Layout

```
backend/
├── bootstrap.php            env loader + autoloader + base constants
├── config/
│   ├── config.php           App\Config\Config (.env + defaults, dot access)
│   └── database.php         gd_database_config() → DSN parts from env
├── core/
│   ├── Database.php         lazy PDO singleton; ERRMODE_EXCEPTION,
│   │                        FETCH_ASSOC, EMULATE_PREPARES=false, utf8mb4
│   ├── Request.php          method/path/query/header wrapper (+ front-controller path normalization)
│   ├── Response.php         ok()/error()/notFound()/methodNotAllowed(), CORS, preflight
│   ├── Router.php           pattern routes, {slug} placeholders, 404/405/OPTIONS
│   └── Validator.php        slug validation
├── controllers/
│   └── ServiceController.php    index() / show(slug); assembles API payloads
├── models/
│   ├── ServiceCategory.php      active lists + by-slug/by-id lookups
│   ├── ServiceItem.php          active lists (global/per-category), ambiguous-slug aware
│   ├── ServiceField.php         fields per items; merges options+sizes; shapes public payload
│   ├── ServiceFieldOption.php   option values grouped by field
│   └── ServiceFieldSize.php     size labels grouped by field
├── routes/
│   └── api.php                  route table
└── middleware/                  (reserved)

public/
├── api.php                front controller (error boundary → safe 500 JSON)
├── router.php             php -S dev router (static pass-through + /api dispatch)
└── .htaccess              Apache rewrite /api/v1/** → api.php

database/
├── schema.sql             5 tables, FKs, indexes, uniques, CHECK constraints
├── catalog.php            canonical catalog data (generated from original QUOTE_CONFIG)
└── seed_services.php      idempotent importer (upserts by natural keys)

storage/logs/api-error.log runtime error log (gitignored)
```

## Request lifecycle

```
GET /api/v1/services/printing
  → .htaccess / router.php → public/api.php
  → backend/bootstrap.php (env, autoload)
  → Router match {slug} = printing
  → ServiceController::show("printing")
      ServiceCategory::findActiveBySlug → hit
      ServiceItem::activeByCategory + ServiceField::forItems (3 queries total)
  → Response::ok() → JSON envelope
```

Any Throwable escaping the router is caught in `api.php`: full details go to `storage/logs/api-error.log`, the client receives only `{ok:false,error:{code:"SERVER_ERROR",…}}` with HTTP 500. `display_errors` output never reaches the client through this path.

## Conventions & decisions

- **Namespaces**: `App\Core`, `App\Controllers`, `App\Models`, `App\Config`; a tiny spl autoloader maps them onto the lowercase directories (`App\Models\ServiceItem` → `backend/models/ServiceItem.php`). Composer autoload is intentionally not used at runtime.
- **No framework**, no external dependencies (`composer.json` declares PHP ≥ 8.1 + ext-pdo only).
- **Active-only reads**: every public query filters `is_active = 1` on categories and items.
- **Image paths** are stored root-relative in the DB (`assets/images/service/01.webp`); the frontend adds its page-depth prefix when rendering.
- **CORS**: opt-in via `ALLOWED_ORIGINS`; same-origin default emits no headers; wildcard unsupported.
- **Seed source**: `database/catalog.php` was machine-generated from the pre-existing `QUOTE_CONFIG`; treat it as the canonical import fixture until the Services CMS takes over writes.

## Explicitly out of scope (later phases)

Auth/sessions · admin dashboard/CMS · POST /api/v1/requests · file upload processing · messaging/notifications · projects · analytics · payments.
