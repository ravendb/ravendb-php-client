RavenDB PHP Client - Release notes
==================================

## 5.2.2

Removed:
- support for php 7.4 and 8.0

## 5.2.1

Added features:

- *session*
    - ability to track objects
    - crud
    - delete
    - include
    - no tracking
    - cluster transactions
    - conditional load

- *attachments*
    - crud
    - session
    - move, rename

- *indexes*
    - crud (static/auto)
    - modify state: (setting index priority, enabling/disabling indexes, start/stop index, list/clean indexing errors, getting terms)

- *query*
    - static/dynamic indexes
    - document query methos (where equals, starts with, etc)
    - aggregation (group by )
    - count, order, take/skip
    - boost, proximity, fuzzy
    - select fields (projection)
    - delete/patch by query

- *https support*
    - certificates crud
    - request executor

- *compare exchange*
    - crud
    - session

- *patch*
    - by script
    - by path

- *databases*
    - crud

