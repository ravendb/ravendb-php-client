# Official PHP client for RavenDB NoSQL Database


## Introduction
PHP client API (v5.2) for [RavenDB](https://ravendb.net/) , a NoSQL document database.

**Package has been made to match Java and other RavenDB clients**


## Installation

Add this library to your project via [Composer](https://getcomposer.org/)

``` bash
$ composer require ravendb/ravendb-php-client
```


## What's new?

#### 5.2.0beta1

- **session**
    - ability to track objects
    - crud
    - include
    - no tracking

- **indexes**
    - crud (static/auto)
    - modify state: (setting index priority, enabling/disabling indexes, start/stop index, list/clean indexing errors, getting terms)

- **query**
    - static/dynamic indexes
    - document query methos (where equals, starts with, etc)
    - aggregation (group by )
    - count, order, take/skip
    - boost, proximity, fuzzy
    - select fields (projection)
    - delete/patch by query

- **https support**
    - certificates crud
    - request executor

- **compare exchange**
    - crud
    - session

- **patch**
    - by script
    - by path

- **databases**
    - crud

> The client is still in the **beta** phase.

----
#### RavenDB Documentation
https://ravendb.net/docs/article-page/5.3/php

-----
##### Bug Tracker
http://issues.hibernatingrhinos.com/issues/RDBC

-----
##### License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
