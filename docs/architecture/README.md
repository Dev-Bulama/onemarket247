# OneMarket247 — Phase 0 Architecture & Planning

This directory contains the complete Phase 0 deliverables for OneMarket247: a
multi-vendor e-commerce marketplace built on Laravel + Filament (web) with a future
React Native mobile client consuming the same backend and MySQL database.

No application code is written in Phase 0. These documents are the contract that
Phases 1–27 (web platform) and, later, Phases 28–37 (API finalization + mobile) are
built against.

## Documents

| # | Document | Contents |
|---|----------|----------|
| 01 | [System Architecture](01-system-architecture.md) | High-level architecture, application modules, user roles, vendor isolation strategy, multi-tenancy approach |
| 02 | [Database ERD](02-database-erd.md) | Entity-relationship plan, table list grouped by domain, key relationships and constraints |
| 03 | [Modules & Roles](03-modules-and-roles.md) | Full module breakdown, permission matrix, role capability map |
| 04 | [Models & Migrations](04-models-and-migrations.md) | Eloquent model list, migration plan/order, enums & status classes |
| 05 | [Filament Resources](05-filament-resources.md) | Admin panel resource list, relation managers, widgets, custom pages |
| 06 | [Web Pages](06-web-pages.md) | Customer-facing marketplace route/page list |
| 07 | [Vendor Dashboard](07-vendor-dashboard.md) | Vendor dashboard page list and capabilities |
| 08 | [API Endpoints](08-api-endpoints.md) | Versioned REST API endpoint map (`/api/v1/*`) for web + future mobile |
| 09 | [Lifecycles](09-lifecycles.md) | Order, payment, refund/return, commission, and withdrawal lifecycles (state machines) |
| 10 | [Security Architecture](10-security-architecture.md) | AuthN/AuthZ, vendor isolation, payment security, data protection, threat mitigations |
| 11 | [Testing Roadmap](11-testing-roadmap.md) | Test strategy and coverage plan per phase |
| 12 | [Deployment Roadmap](12-deployment-roadmap.md) | Environments, CI/CD, infra, backup/DR strategy |
| 13 | [Development Roadmap](13-development-roadmap.md) | Full phase-by-phase plan (Phase 0 → 37) with completion gates, including the Web Completion Gate |

## Phase 0 Completion Gate

Phase 1 does not start until:

- [x] Architecture is complete (this document set)
- [x] Major modules are identified ([03](03-modules-and-roles.md))
- [x] Database relationships are defined ([02](02-database-erd.md))
- [x] Security boundaries are defined ([10](10-security-architecture.md))
- [x] Parent and vendor sub-order structures are defined ([09](09-lifecycles.md))
- [x] Commission and wallet flows are defined ([09](09-lifecycles.md))
- [x] The phased development order is documented ([13](13-development-roadmap.md))

## Phase completion reports

As each implementation phase finishes, its completion report is added under
[`docs/reports/`](../reports/). Phase 1 (Laravel Project Foundation) is complete —
see [`docs/reports/phase-01-completion-report.md`](../reports/phase-01-completion-report.md).

## Hard constraint carried through every later phase

**No React Native code, screens, or project files are created until the Web
Marketplace Completion Report (end of Phase 27 / Web Completion Gate) and the API
Finalization Report (end of Phase 28) both exist.** See
[13-development-roadmap.md](13-development-roadmap.md) for the gate definition and the
report templates that must be produced before Phase 29 begins.
