# PHP client for RavenDB NoSQL Database

## Installation

You can install library to your project via [Composer](https://getcomposer.org/)
``` bash
$ composer require ravendb/ravendb-php-client
```

## Releases and Changelog - [click here](https://github.com/ravendb/ravendb-php-client/releases)

## Documentation

This readme provides short examples for the following: 

  [Getting started](#getting-started),<!--
  [Asynchronous call types](#supported-asynchronous-call-types),
-->  
  [Crud example](#crud-example),  
  [Query documents](#query-documents),  
  [Attachments](#attachments),  
  [Time series](#timeseries),<!--  
  [Bulk insert](#bulk-insert),
  [Changes API](#changes-api),  
  [Streaming](#streaming),  
  [Revisions](#revisions),  
  [Suggestions](#suggestions),
-->  
  [Patching](#advanced-patching),<!--
  [Subscriptions](#subscriptions),  
  [Using object literals](#using-object-literals-for-entities),
-->  
  [Using classes](#using-classes-for-entities),  
  [PHP usage](#usage-with-php),  
  [Working with secure server](#working-with-a-secure-server),  
  [Running tests](#running-tests)  

For more information go to the online [RavenDB Documentation](https://ravendb.net/docs/article-page/latest/nodejs/client-api/what-is-a-document-store).

For more information on how to use **RavenDB** with **Laravel** 
check out the [Raven Laravel Demo Application](https://github.com/ravendb/samples-php-laravel)


## Getting started

1. Require the `DocumentStore` class from the ravendb package
```php
use RavenDB\Documents\DocumentStore;
```

2. Initialize the document store (you should have a single DocumentStore instance per application)
```php
    $store = new DocumentStore('http://live-test.ravendb.net', 'databaseName');
    $store->initialize();
```

3. Open a session
```php
    $session = $store->openSession();
```

4. Call `saveChanges()` when you're done
```php
    $user = $session->load('users/1-A'); // Load document
    $user->setPassword(PBKDF2('new password')); // Update data
    
    $session->saveChanges(); // Save changes
    // Data is now persisted
    // You can proceed e.g. finish web request
    
```

<!--
## Supported asynchronous call types

Most methods on the session object are asynchronous and return a Promise.  
Either use `async & await` or `.then()` with callback functions.

1. async / await
```javascript
const session = store.openSession();
let user = await session.load('users/1-A');
user.password = PBKDF2('new password');
await session.saveChanges();
```

2. .then & callback functions
```javascript
session.load('Users/1-A')
    .then((user) => {
        user.password = PBKDF2('new password');
    })
    .then(() => session.saveChanges())
    .then(() => {
        // here session is complete
    });
```

>##### Related tests:
> <small>[async and await](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L55) </small>  
> <small>[then and callbacks](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L72) </small>
-->

## CRUD example

### Store documents

```php
$product = new Product();
$product->setTitle("iPhone X");
$product->setPrice(999.99);
$product->setCurrency("USD");
$product->setStorage(64);
$product->setManufacturer("Apple");
$product->setInStock(true);

$session->store($product, 'products/1-A');
echo $product->id; // products/1-A

$session->saveChanges();
```

>##### Related tests:
> <!-- <small>[store()](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/SessionApiTests.ts#L21) </small> -->  
> <!-- <small>[ID generation - session.store()](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/IdGeneration.ts#L9) </small> -->  
> <!-- <small>[store document with @metadata](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Issues/RDBC_213.ts#L16) </small>  -->
> <small>[storing docs with same ID in same session should throw](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/TrackEntityTest.php#L80) </small> 

### Load documents

```php
$product = $session->load(Product::class, 'products/1-A');
echo $product->getTitle(); // iPhone X
echo $product->getId();    // products/1-A
```

<!-- > ##### Related tests: -->
<!-- > <small>[load()](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/SessionApiTests.ts#L64) </small> -->

### Load documents with include

```php
// users/1
// {
//      "name": "John",
//      "kids": ["users/2", "users/3"]
// }

$session = $store->openSession();

try {
    $user1 = $session
        ->include("kids")
        ->load("users/1");
        // Document users/1 and all docs referenced in "kids"
        // will be fetched from the server in a single request.

    $user2 = $session->load("users/2"); // this won't call server again

    $this->assertNotNull($user1);
    $this->assertNotNull($user2);
    $this->assertEqual(1, $session->advanced()->getNumberOfRequests());
} finally {
    $session->close();
}


```

>##### Related tests:
> <small>[can load with includes](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/Documents/LoadTest/LoadTest.php#L10) </small>
<!-- > <small>[loading data with include](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L128) </small>  -->
<!-- > <small>[loading data with passing includes](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L148) </small> -->

### Update documents

```php
$product = $session->load(Product::class, 'products/1-A');
$product->setInStock(false);
$product->setLastUpdate(new Date());
$session->saveChanges();
// ...
$product = $session->load(Product::class, 'products/1-A');
echo $product->getInStock();    // false
echo $product->getLastUpdate(); // the current date
```

<!-- >##### Related tests: -->
<!-- > <small>[update document](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L170) </small> --> 
<!-- > <small>[update document metadata](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Issues/RDBC_213.ts#L35) </small> -->

### Delete documents

1. Using entity
```php
$product = $session->load('products/1-A');
$session->delete($product);
$session->saveChanges();

$product = $session->load('products/1-A');
$this->assertNull($product); // null
```

2. Using document ID
```php
$session->delete('products/1-A');
```

>##### Related tests:
> <small>[delete doc by entity](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/DeleteTest.php#L10) </small>  
> <small>[delete doc by ID](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/DeleteTest.php#L38) </small>  
> <small>[onBeforeDelete is called before delete by ID](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Issues/RavenDB_15492Test.php#L9) </small>    
> <small>[cannot delete untracked entity](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/TrackEntityTest.php#L15) </small>  
> <small>[loading deleted doc returns null](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/TrackEntityTest.php#L37) </small>  

## Query documents

1. Use `query()` session method:

Query by collection:
```php
$query = $session->query(Product::class, Query::collection('products'));
```
Query by index name:
```php
$query = $session->query(Product::class, Query::indexName('productsByCategory'));
```
Query by index:
```php
$query = $session->query(Product::class, Products_ByCategory::class);
```
Query by entity type:
```php
$query = $session->query(Product::class);
```

2. Build up the query - apply search conditions, set ordering, etc.  
   Query supports chaining calls:
```php
$query
    ->waitForNonStaleResults()
    ->usingDefaultOperator('AND') 
    ->whereEquals('manufacturer', 'Apple')
    ->whereEquals('in_stock', true)
    ->whereBetween('last_update', new DateTime('- 1 week'), new DateTime())
    ->orderBy('price');
```

3. Execute the query to get results:
```php
$results = $query->toList(); // get all results
// ...
$firstResult = $query->first(); // gets first result
// ...
$single = $query->single();  // gets single result 
```

### Query methods overview

#### selectFields() - projections using a single field
```php
// RQL
// from users select name

// Query
$userNames = $session->query(User::class)
    ->selectFields("name")
    ->toList();

// Sample results
// John, Stefanie, Thomas
```

>##### Related tests:
> <!-- <small>[projections single field](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L341) </small>  -->  
> <small>[query single property](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/_QueryTest/QueryTest.php#L368) </small>
> <!-- <small>[retrieve camel case with projection](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/CustomKeyCaseConventionsTests.ts#L288) </small>  -->  
> <small>[can_project_id_field](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Issues/RavenDB_14811Test/RavenDB_14811Test.php#L58) </small>

#### selectFields() - projections using multiple fields
```php
// RQL
// from users select name, age

// Query
$session->query(User::class)
    ->selectFields([ "name", "age" ])
    ->toList();

// Sample results
// [ [ name: 'John', age: 30 ],
//   [ name: 'Stefanie', age: 25 ],
//   [ name: 'Thomas', age: 25 ] ]
```

>##### Related tests:
> <!-- <small>[projections multiple fields](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L349) </small>  -->  
> <small>[query with projection](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/_QueryTest/QueryTest.php#L555) </small><!-- 
> <small>[retrieve camel case with projection](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/CustomKeyCaseConventionsTests.ts#L288) </small>  -->  
> <small>[can_project_id_field](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Issues/RavenDB_14811Test/RavenDB_14811Test.php#L58) </small> 

#### distinct()
```php
// RQL
// from users select distinct age

// Query
$session->query(User::class)
    ->selectFields("age")
    ->distinct()
    ->toList();

// Sample results
// [ 30, 25 ]
```

>##### Related tests:
> <!-- <small>[distinct](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L360) </small>  -->  
> <small>[query distinct](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/_QueryTest/QueryTest.php#L611) </small>

#### whereEquals() / whereNotEquals()
```php
// RQL
// from users where age = 30 

// Query
$session->query(User::class)
    ->whereEquals("age", 30)
    ->toList();

// Sample results
// [ User {
//    name: 'John',
//    age: 30,
//    kids: [...],
//    registeredAt: 2017-11-10T23:00:00.000Z } ]
```

>##### Related tests:  
> <!-- <small>[where equals](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L369) </small>  -->  
> <small>[where equals](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/_QueryTest/QueryTest.php#L784) </small>  
> <small>[where not equals](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/_QueryTest/QueryTest.php#L817) </small>  

#### whereIn()
```php
// RQL
// from users where name in ("John", "Thomas")

// Query
$session->query(User::class)
    ->whereIn("name", ["John", "Thomas"])
    ->toList();

// Sample results
// [ User {
//     name: 'John',
//     age: 30,
//     registeredAt: 2017-11-10T23:00:00.000Z,
//     kids: [...],
//     id: 'users/1-A' },
//   User {
//     name: 'Thomas',
//     age: 25,
//     registeredAt: 2016-04-24T22:00:00.000Z,
//     id: 'users/3-A' } ]
```

>##### Related tests:  
> <!-- <small>[where in](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L377) </small>  -->
> <small>[query with where in](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/_QueryTest/QueryTest.php#L416) </small>  

#### whereStartsWith() / whereEndsWith()
```php
// RQL
// from users where startsWith(name, 'J')

// Query
$session->query(User::class)
    ->whereStartsWith("name", "J")
    ->toList();

// Sample results
// [ User {
//    name: 'John',
//    age: 30,
//    kids: [...],
//    registeredAt: 2017-11-10T23:00:00.000Z } ]
```

>##### Related tests:  
> <small>[query with where clause](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/_QueryTest/QueryTest.php#L233) </small>  


#### whereBetween()
```php
// RQL
// from users where registeredAt between '2016-01-01' and '2017-01-01'

// Query
$session->query({ collection: "users" })
    ->whereBetween("registeredAt", DateTime::createFromFormat('Y-m-d', '2016-01-01'), DateTime::createFromFormat('Y-m-d', '2017-01-01'))
    ->toList();

// Sample results
// [ User {
//     name: 'Thomas',
//     age: 25,
//     registeredAt: 2016-04-24T22:00:00.000Z,
//     id: 'users/3-A' } ]
```

>##### Related tests:  
> <!-- <small>[where between](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L385) </small>  -->  
> <small>[query with where between](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/_QueryTest/QueryTest.php#L438) </small>  

#### whereGreaterThan() / whereGreaterThanOrEqual() / whereLessThan() / whereLessThanOrEqual()
```php
// RQL
// from users where age > 29

// Query
$session->query(User::class)
    ->whereGreaterThan("age", 29)
    ->toList();

// Sample results
// [ User {
//   name: 'John',
//   age: 30,
//   registeredAt: 2017-11-10T23:00:00.000Z,
//   kids: [...],
//   id: 'users/1-A' } ]
```

>##### Related tests:  
> <!-- <small>[where greater than](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L393) </small>  -->  
> <small>[query with where less than](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/_QueryTest/QueryTest.php#L463) </small>  
> <small>[query with where less than or equal](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/_QueryTest/QueryTest.php#L486) </small>    
> <small>[query with where greater than](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/_QueryTest/QueryTest.php#L507) </small>  
> <small>[query with where greater than or equal](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/_QueryTest/QueryTest.php#L532) </small>  

#### whereExists()
Checks if the field exists.
```php
// RQL
// from users where exists("age")

// Query
$session->query(User::class)
    ->whereExists("kids")
    ->toList();

// Sample results
// [ User {
//   name: 'John',
//   age: 30,
//   registeredAt: 2017-11-10T23:00:00.000Z,
//   kids: [...],
//   id: 'users/1-A' } ]
```

>##### Related tests:  
> <!-- <small>[where exists](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L401) </small>  -->  
> <small>[query where exists](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/_QueryTest/QueryTest.php#L997) </small>  

#### containsAny() / containsAll()
```php
// RQL
// from users where kids in ('Mara')

// Query
$session->query(User::class)
    ->containsAll("kids", ["Mara", "Dmitri"])
    ->toList();

// Sample results
// [ User {
//   name: 'John',
//   age: 30,
//   registeredAt: 2017-11-10T23:00:00.000Z,
//   kids: ["Dmitri", "Mara"]
//   id: 'users/1-A' } ]
```

>##### Related tests:  
> <!-- <small>[where contains any](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L409) </small>  -->  
> <small>[queries with contains](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/ContainsTest/ContainsTest.php#L12) </small>  

#### search()
Perform full-text search.
```php
// RQL
// from users where search(kids, 'Mara')

// Query
$session->query(User::class)
    ->search("kids", "Mara Dmitri")
    ->toList();

// Sample results
// [ User {
//   name: 'John',
//   age: 30,
//   registeredAt: 2017-11-10T23:00:00.000Z,
//   kids: ["Dmitri", "Mara"]
//   id: 'users/1-A' } ]
```

>##### Related tests:  
> <!-- <small>[search()](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L417) </small>  -->  
> <small>[query search with or](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/_QueryTest/QueryTest.php#L636) </small>    
> <small>[query_CreateClausesForQueryDynamicallyWithOnBeforeQueryEvent](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/_QueryTest/QueryTest.php#L35) </small>  

#### openSubclause() / closeSubclause()
```php
// RQL
// from users where exists(kids) or (age = 25 and name != Thomas)

// Query
$session->query(User::class)
    ->whereExists("kids")
    ->orElse()
    ->openSubclause()
        ->whereEquals("age", 25)
        ->whereNotEquals("name", "Thomas")
    ->closeSubclause()
    ->toList();

// Sample results
// [ User {
//     name: 'John',
//     age: 30,
//     registeredAt: 2017-11-10T23:00:00.000Z,
//     kids: ["Dmitri", "Mara"]
//     id: 'users/1-A' },
//   User {
//     name: 'Stefanie',
//     age: 25,
//     registeredAt: 2015-07-29T22:00:00.000Z,
//     id: 'users/2-A' } ]
```

>##### Related tests:  
> <!-- <small>[subclause](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L425) </small>  -->  
> <small>[working with subclause](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/Issues/RavenDB_5669Test/RavenDB_5669Test.php#L44) </small>  

#### not()
```php
// RQL
// from users where age != 25

// Query
$session->query(User::class)
    ->not()
    ->whereEquals("age", 25)
    ->toList();

// Sample results
// [ User {
//   name: 'John',
//   age: 30,
//   registeredAt: 2017-11-10T23:00:00.000Z,
//   kids: ["Dmitri", "Mara"]
//   id: 'users/1-A' } ]
```

>##### Related tests:  
> <!-- <small>[not()](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L438) </small>  -->  
> <small>[query where not](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/_QueryTest/QueryTest.php#L817) </small>  

#### orElse() / andAlso()
```php
// RQL
// from users where exists(kids) or age < 30

// Query
$session->query(User::class)
    ->whereExists("kids")
    ->orElse()
    ->whereLessThan("age", 30)
    ->toList();

// Sample results
//  [ User {
//     name: 'John',
//     age: 30,
//     registeredAt: 2017-11-10T23:00:00.000Z,
//     kids: [ 'Dmitri', 'Mara' ],
//     id: 'users/1-A' },
//   User {
//     name: 'Thomas',
//     age: 25,
//     registeredAt: 2016-04-24T22:00:00.000Z,
//     id: 'users/3-A' },
//   User {
//     name: 'Stefanie',
//     age: 25,
//     registeredAt: 2015-07-29T22:00:00.000Z,
//     id: 'users/2-A' } ]
```

>##### Related tests:  
> <!-- <small>[orElse](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L447) </small>  -->  
> <small>[working with subclause](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Ported/Issues/RavenDB_5669.ts#L40) </small>  

#### usingDefaultOperator()
If neither `andAlso()` nor `orElse()` is called then the default operator between the query filtering conditions will be `AND` .  
You can override that with `usingDefaultOperator` which must be called before any other where conditions.
```php
// RQL
// from users where exists(kids) or age < 29

// Query
$session->query(User::class)
    ->usingDefaultOperator("OR") // override the default 'AND' operator
    ->whereExists("kids")
    ->whereLessThan("age", 29)
    ->toList();

// Sample results
//  [ User {
//     name: 'John',
//     age: 30,
//     registeredAt: 2017-11-10T23:00:00.000Z,
//     kids: [ 'Dmitri', 'Mara' ],
//     id: 'users/1-A' },
//   User {
//     name: 'Thomas',
//     age: 25,
//     registeredAt: 2016-04-24T22:00:00.000Z,
//     id: 'users/3-A' },
//   User {
//     name: 'Stefanie',
//     age: 25,
//     registeredAt: 2015-07-29T22:00:00.000Z,
//     id: 'users/2-A' } ]
```

<!-- >##### Related tests:  -->
> <!-- <small>[set default operator](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L457) </small>  -->  
> <!-- <small>[AND is used when default operator is not set](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Issues/RDBC_649.ts#L36) </small>    -->
> <!-- <small>[set default operator to OR](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Issues/RDBC_649.ts#L45) </small>  -->

#### orderBy() / orderByDesc() / orderByScore() / randomOrdering()
```php
// RQL
// from users order by age

// Query
$session->query(User::class)
    ->orderBy("age")
    ->toList();

// Sample results
// [ User {
//     name: 'Stefanie',
//     age: 25,
//     registeredAt: 2015-07-29T22:00:00.000Z,
//     id: 'users/2-A' },
//   User {
//     name: 'Thomas',
//     age: 25,
//     registeredAt: 2016-04-24T22:00:00.000Z,
//     id: 'users/3-A' },
//   User {
//     name: 'John',
//     age: 30,
//     registeredAt: 2017-11-10T23:00:00.000Z,
//     kids: [ 'Dmitri', 'Mara' ],
//     id: 'users/1-A' } ]
```

>##### Related tests:  
> <!-- <small>[orderBy()](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L467) </small>  -->  
> <!-- <small>[orderByDesc()](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L477) </small>   --> 
> <small>[query random order](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/_QueryTest/QueryTest.php#L817) </small>  
> <small>[order by AlphaNumeric](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/_QueryTest/QueryTest.php#L1103) </small>  
> <small>[query with boost - order by score](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/_QueryTest/QueryTest.php#L1026) </small>  

#### take()
Limit the number of query results.
```php
// RQL
// from users order by age

// Query
$session->query(User::class)
    ->orderBy("age") 
    ->take(2) // only the first 2 entries will be returned
    ->toList();

// Sample results
// [ User {
//     name: 'Stefanie',
//     age: 25,
//     registeredAt: 2015-07-29T22:00:00.000Z,
//     id: 'users/2-A' },
//   User {
//     name: 'Thomas',
//     age: 25,
//     registeredAt: 2016-04-24T22:00:00.000Z,
//     id: 'users/3-A' } ]
```

>##### Related tests:  
> <!-- <small>[take()](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L487) </small>  -->  
> <small>[query skip take](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/_QueryTest/QueryTest.php#L685) </small>  
> <!-- <small>[canUseOffsetWithCollectionQuery](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Ported/Issues/RavenDB_17551.ts#L17) </small>  -->

#### skip()
Skip a specified number of results from the start.
```php
// RQL
// from users order by age

// Query
$session->query(User::class)
    ->orderBy("age") 
    ->take(1) // return only 1 result
    ->skip(1) // skip the first result, return the second result
    ->toList();

// Sample results
// [ User {
//     name: 'Thomas',
//     age: 25,
//     registeredAt: 2016-04-24T22:00:00.000Z,
//     id: 'users/3-A' } ]
```

>##### Related tests:  
> <!-- <small>[skip()](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L496) </small>  -->  
> <small>[query skip take](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/_QueryTest/QueryTest.php#L685) </small>  
> <!-- <small>[canUseOffsetWithCollectionQuery](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Ported/Issues/RavenDB_17551.ts#L17) </small>  -->

#### Getting query statistics
Use the `statistics()` method to obtain query statistics.
```php
// Query
$stats = new QueryStatistics();
$results = $session->query(User::class)
    ->whereGreaterThan("age", 29)
    ->statistics($stats)
    ->toList();

// Sample results
// QueryStatistics {
//   isStale: false,
//   durationInMs: 744,
//   totalResults: 1,
//   skippedResults: 0,
//   timestamp: 2018-09-24T05:34:15.260Z,
//   indexName: 'Auto/users/Byage',
//   indexTimestamp: 2018-09-24T05:34:15.260Z,
//   lastQueryTime: 2018-09-24T05:34:15.260Z,
//   resultEtag: 8426908718162809000 }
```

<!-- >##### Related tests:  -->
> <!-- <small>[can get stats](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L506) </small>  -->

#### all() / first() / single() / count()
`all()` - returns all results

`first()` - first result only

`single()` - first result, throws error if there's more entries

`count()` - returns the number of entries in the results (not affected by `take()`)

>##### Related tests:  
> <small>[query first and single](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/_QueryTest/QueryTest.php#L917) </small>    
> <small>[query count](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/_QueryTest/QueryTest.php#L951) </small>  

## Attachments

#### Store attachments
```php
$doc = new User();
$doc->setName('John');

// Store a document, the entity will be tracked.
$session->store($doc);

// Get read stream or buffer to store
$fileStream = file_get_contents("../photo.png");

// Store attachment using entity
$session->advanced()->attachments()->store($doc, "photo.png", $fileStream, "image/png");

// OR store attachment using document ID
$session->advanced()->attachments()->store($doc->getId(), "photo.png", $fileStream, "image/png");

// Persist all changes
$session->saveChanges();
```

>##### Related tests:  
> <!-- <small>[store attachment](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L203) </small>  -->  
> <small>[can put attachments](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/Attachments/AttachmentsSessionTest.php#L15) </small>    
> <!-- <small>[checkIfHasChangesIsTrueAfterAddingAttachment](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Ported/Issues/RavenDB_16985.ts#L17) </small>  -->  
> <!-- <small>[store many attachments and docs with bulk insert](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Ported/Attachments/BulkInsertAttachmentsTest.ts#L105) </small>  -->

#### Get attachments
```php
// Get an attachment
$attachment = $session->advanced()->attachments()->get($documentId, "photo.png")

// Attachment.details contains information about the attachment:
//     { 
//       name: 'photo.png',
//       documentId: 'users/1-A',
//       contentType: 'image/png',
//       hash: 'MvUEcrFHSVDts5ZQv2bQ3r9RwtynqnyJzIbNYzu1ZXk=',
//       changeVector: '"A:3-K5TR36dafUC98AItzIa6ow"',
//       size: 4579 
//     }

// Attachment.data is a Readable.
$fileBytes = $attachment->getData();
file_put_contents('../photo.png', $fileBytes);
```

>##### Related tests:  
> <!-- <small>[get attachment](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L241) </small>  -->  
> <small>[can get & delete attachments](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/Attachments/AttachmentsSessionTest.php#L144) </small>  

#### Check if attachment exists
```php
$session->advanced()->attachments()->exists($doc->getId(), "photo.png");
// true

$session->advanced()->attachments()->exists($doc->getId(), "not_there.avi");
// false
```

>##### Related tests:  
> <!-- <small>[attachment exists](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L258) </small>  -->  
> <small>[attachment exists 2](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/Attachments/AttachmentsSessionTest.php#L419) </small>  

#### Get attachment names
```php
// Use a loaded entity to determine attachments' names
$session->advanced()->attachments()->getNames($doc);

// Sample results:
// [ { name: 'photo.png',
//     hash: 'MvUEcrFHSVDts5ZQv2bQ3r9RwtynqnyJzIbNYzu1ZXk=',
//     contentType: 'image/png',
//     size: 4579 } ]
```
>##### Related tests:  
> <!-- <small>[get attachment names](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L266) </small>  -->  
> <small>[get attachment names 2](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/Attachments/AttachmentsSessionTest.php#L376) </small>  


## TimeSeries

#### Store time series
```php
$session = $store->openSession();

// Create a document with time series
$session->store(new User(), "users/1");
$tsf = $session->timeSeriesFor("users/1", "heartbeat");

// Append a new time series entry
$tsf->append(new DateTime(), 120);

$session->saveChanges();
```

>##### Related tests:  
> <!-- <small>[can use time series](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L759) </small>    -->
> <small>[canCreateSimpleTimeSeries](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/TimeSeries/TimeSeriesSessionTest.php#L16) </small>    
> <small>[usingDifferentTags](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/TimeSeries/TimeSeriesSessionTest.php#L244) </small>    
> <small>[canStoreAndReadMultipleTimestamps](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/TimeSeries/TimeSeriesSessionTest.php#L384) </small>   
> <small>[canStoreLargeNumberOfValues](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/TimeSeries/TimeSeriesSessionTest.php#L441) </small>    
> <small>[shouldDeleteTimeSeriesUponDocumentDeletion](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/TimeSeries/TimeSeriesSessionTest.php#L796) </small>  

#### Get time series for document
```php
$session = $store->openSession();

// Get time series for document by time series name
$tsf = $session->timeSeriesFor("users/1", "heartbeat");

// Get all time series entries
$heartbeats = $tsf->get();
```

>##### Related tests:  
> <small>[canCreateSimpleTimeSeries](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/TimeSeries/TimeSeriesSessionTest.php#L16) </small>    
> <small>[canStoreLargeNumberOfValues](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/TimeSeries/TimeSeriesSessionTest.php#L441) </small>    
> <small>[canRequestNonExistingTimeSeriesRange](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/TimeSeries/TimeSeriesSessionTest.php#L574) </small>    
> <small>[canGetTimeSeriesNames2](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/TimeSeries/TimeSeriesSessionTest.php#L701) </small>    
> <small>[canSkipAndTakeTimeSeries](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/TimeSeries/TimeSeriesSessionTest.php#L850) </small>  

<!-- 
## Bulk Insert

```javascript
// Create a bulk insert instance from the DocumentStore
const bulkInsert = store.bulkInsert();

// Store multiple documents
for (const name of ["Anna", "Maria", "Miguel", "Emanuel", "Dayanara", "Aleida"]) {
    const user = new User({ name });
    await bulkInsert.store(user);
}

// Sample documents stored:
// User { name: 'Anna', id: 'users/1-A' }
// User { name: 'Maria', id: 'users/2-A' }
// User { name: 'Miguel', id: 'users/3-A' }
// User { name: 'Emanuel', id: 'users/4-A' }
// User { name: 'Dayanara', id: 'users/5-A' }
// User { name: 'Aleida', id: 'users/6-A' }

// Persist the data - call finish
await bulkInsert.finish();
```

>##### Related tests:
> <small>[bulk insert example](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L279) </small>  
> <small>[simple bulk insert should work](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Ported/BulkInsert/BulkInsertsTest.ts#L23) </small>  
> <small>[bulk insert can be aborted](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Ported/BulkInsert/BulkInsertsTest.ts#L95) </small>  
> <small>[can modify metadata with bulk insert](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Ported/BulkInsert/BulkInsertsTest.ts#L136) </small>

## Changes API

Listen for database changes e.g. document changes.

```javascript
// Subscribe to change notifications
const changes = store.changes();

// Subscribe for all documents, or for specific collection (or other database items)
const docsChanges = changes.forAllDocuments();

// Handle changes events 
docsChanges.on("data", change => {
    // A sample change data recieved:
    // { type: 'Put',
    //   id: 'users/1-A',
    //   collectionName: 'Users',
    //   changeVector: 'A:2-QCawZTDbuEa4HUBORhsWYA' }
});

docsChanges.on("error", err => {
    // handle errors
})

{
    const session = store.openSession();
    await session.store(new User({ name: "Starlord" }));
    await session.saveChanges();
}

// ...
// Dispose the changes instance when you're done
changes.dispose();
```

>##### Related tests:
> <small>[listen to changes](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L306) </small>  
> <small>[can obtain single document changes](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Ported/Server/Documents/Notifications/ChangesTest.ts#L25) </small>  
> <small>[can obtain all documents changes](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Ported/Server/Documents/Notifications/ChangesTest.ts#L93) </small>  
> <small>[can obtain notification about documents starting with](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Ported/Server/Documents/Notifications/ChangesTest.ts#L255) </small>  
> <small>[can obtain notification about documents in collection](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Ported/Server/Documents/Notifications/ChangesTest.ts#L312) </small>

## Streaming

#### Stream documents by ID prefix
```javascript
// Filter streamed results by passing an ID prefix
// The stream() method returns a Node.js ReadableStream
const userStream = await session.advanced.stream("users/");

// Handle stream events with callback functions
userStream.on("data", user => {
    // Get only documents with ID that starts with 'users/' 
    // i.e.: User { name: 'John', id: 'users/1-A' }
});

userStream.on("error", err => {
    // handle errors
})
```

>##### Related tests:
> <small>[can stream users by prefix](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L525) </small>  
> <small>[can stream documents starting with](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Ported/Core/Streaming/DocumentStreaming.ts#L39) </small>

#### Stream documents by query
```javascript
// Define a query
const query = session.query({ collection: "users" }).whereGreaterThan("age", 29);

let streamQueryStats;
// Call stream() to execute the query, it returns a Node.js ReadableStream.
// Can get query stats by passing a stats callback to stream() method
const queryStream = await session.advanced.stream(query, _ => streamQueryStats = _);

// Handle stream events with callback functions
queryStream.on("data", user => {
    // Only documents matching the query are received
    // These entities are Not tracked by the session
});

// Can get query stats by using an event listener
queryStream.once("stats", queryStats => {
    // Sample stats:
    // { resultEtag: 7464021133404493000,
    //   isStale: false,
    //   indexName: 'Auto/users/Byage',
    //   totalResults: 1,
    //   indexTimestamp: 2018-10-01T09:04:07.145Z }
});

// Stream emits an 'end' event when there is no more data to read
queryStream.on("end", () => {
   // Get info from 'streamQueryStats', the stats object
   const totalResults = streamQueryStats.totalResults;
   const indexUsed = streamQueryStats.indexName;
});

queryStream.on("error", err => {
    // handle errors
});
```

>##### Related tests:
> <small>[can stream query and get stats](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L546) </small>  
> <small>[can stream query results](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Ported/Core/Streaming/QueryStreaming.ts#L76) </small>  
> <small>[can stream query results with query statistics](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Ported/Core/Streaming/QueryStreaming.ts#L140) </small>  
> <small>[can stream raw query results](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Ported/Core/Streaming/QueryStreaming.ts#L192) </small>

## Revisions

NOTE: Please make sure revisions are enabled before trying the below.

```javascript
const session = store.openSession();
const user = {
    name: "Marcin",
    age: 30,
    pet: "Cat"
};

// Store a document
await session.store(user, "users/1");
await session.saveChanges();

// Modify the document to create a new revision
user.name = "Roman";
user.age = 40;
await session.saveChanges();

// Get revisions
const revisions = await session.advanced.revisions.getFor("users/1");

// Sample results:
// [ { name: 'Roman',
//     age: 40,
//     pet: 'Cat',
//     '@metadata': [Object],
//     id: 'users/1' },
//   { name: 'Marcin',
//     age: 30,
//     pet: 'Cat',
//     '@metadata': [Object],
//     id: 'users/1' } ]
```

>##### Related tests:
> <small>[can get revisions](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L737) </small>  
> <small>[canGetRevisionsByDate](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Issues/RavenDB_11770.ts#L21) </small>  
> <small>[can handle revisions](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Ported/RevisionsTest.ts#L35) </small>  
> <small>[canGetRevisionsByChangeVectors](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Ported/RevisionsTest.ts#L149) </small>

## Suggestions

Suggest options for similar/misspelled terms

```javascript
// Some documents in users collection with misspelled name term
// [ User {
//     name: 'Johne',
//     age: 30,
//     ...
//     id: 'users/1-A' },
//   User {
//     name: 'Johm',
//     age: 31,
//     ...
//     id: 'users/2-A' },
//   User {
//     name: 'Jon',
//     age: 32,
//     ...
//     id: 'users/3-A' },
// ]

// Static index definition
class UsersIndex extends AbstractJavaScriptIndexCreationTask {
    constructor() {
        super();
        this.map(User, doc => {
            return {
                name: doc.name
            }
        });
        
        // Enable the suggestion feature on index-field 'name'
        this.suggestion("name"); 
    }
}

// ...
const session = store.openSession();

// Query for similar terms to 'John'
// Note: the term 'John' itself will Not be part of the results

const suggestedNameTerms = await session.query(User, UsersIndex)
    .suggestUsing(x => x.byField("name", "John")) 
    .execute();

// Sample results:
// { name: { name: 'name', suggestions: [ 'johne', 'johm', 'jon' ] } }
```

>##### Related tests:
> <small>[can suggest](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L581) </small>  
> <small>[canChainSuggestions](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Issues/RavenDB_9584.ts#L19) </small>  
> <small>[canUseAliasInSuggestions](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Issues/RavenDB_9584.ts#L42) </small>  
> <small>[canUseSuggestionsWithAutoIndex](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Issues/RavenDB_9584.ts#L60) </small>  
> <small>[can suggest using linq](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Ported/Suggestions/SuggestionsTest.ts#L39) </small>  
> <small>[can suggest using multiple words](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Ported/Suggestions/SuggestionsTest.ts#L78) </small>  
> <small>[can get suggestions with options](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Ported/Suggestions/SuggestionsTest.ts#L125) </small>

-->

## Advanced patching

```php
// Increment 'age' field by 1
$session->advanced()->increment("users/1", "age", 1);

// Set 'underAge' field to false
$session->advanced->patch("users/1", "underAge", false);

$session->saveChanges();
```

>##### Related tests:    
> <!-- <small>[can use advanced.patch](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L708) </small>  -->
> <small>[can patch](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/_FirstClassPatchTest/FirstClassPatchTest.php#L19) </small>    
> <small>[can patch complex](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/_FirstClassPatchTest/FirstClassPatchTest.php#L112) </small>    
> <small>[can add to array](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/_FirstClassPatchTest/FirstClassPatchTest.php#L206) </small>    
> <small>[can increment](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/_FirstClassPatchTest/FirstClassPatchTest.php#L368) </small>    
> <small>[patchWillUpdateTrackedDocumentAfterSaveChanges](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Issues/RavenDB_11552Test.php#L17) </small>  
> <small>[can patch single document](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/PatchTest.php#L17) </small>   
> <!-- <small>[can patch multiple documents](https://github.com/ravendb/ravendb-php-client/blob/282c7bf6d2580ba446e878498d215a38caa67799/tests/Test/Client/PatchTest.php#L89) </small>  -->

<!--

## Subscriptions

```javascript
// Create a subscription task on the server
// Documents that match the query will be send to the client worker upon opening a connection

const subscriptionName = await store.subscriptions.create({
    query: "from users where age >= 30"
});

// Open a connection
// Create a subscription worker that will consume document batches sent from the server
// Documents are sent from the last document that was processed for this subscription

const subscriptionWorker = store.subscriptions.getSubscriptionWorker({ subscriptionName });

// Worker handles incoming batches
subscriptionWorker.on("batch", (batch, callback) => {
    try {
        // Process the incoming batch items
        // Sample batch.items:
        // [ Item {
        //     changeVector: 'A:2-r6nkF5nZtUKhcPEk6/LL+Q',
        //     id: 'users/1-A',
        //     rawResult:
        //      { name: 'John',
        //        age: 30,
        //        registeredAt: '2017-11-11T00:00:00.0000000',
        //        kids: [Array],
        //        '@metadata': [Object],
        //        id: 'users/1-A' },
        //     rawMetadata:
        //      { '@collection': 'Users',
        //        '@nested-object-types': [Object],
        //        'Raven-Node-Type': 'User',
        //        '@change-vector': 'A:2-r6nkF5nZtUKhcPEk6/LL+Q',
        //        '@id': 'users/1-A',
        //        '@last-modified': '2018-10-18T11:15:51.4882011Z' },
        //     exceptionMessage: undefined } ]
        // ...

        // Call the callback once you're done
        // The worker will send an acknowledgement to the server, so that server can send next batch
        callback();
        
    } catch(err) {
        // If processing fails for a particular batch then pass the error to the callback
        callback(err);
    }
});

subscriptionWorker.on("error", err => {
   // handle errors
});

// Subscription event types: 
'batch', 'error', 'end', 'unexpectedSubscriptionError', 'afterAcknowledgment', 'connectionRetry'
```

>##### Related tests:
> <small>[can subscribe](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L607) </small>  
> <small>[should stream all documents](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Ported/Subscriptions/SubscriptionsBasicTest.ts#L143) </small>  
> <small>[should send all new and modified docs](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Ported/Subscriptions/SubscriptionsBasicTest.ts#L202) </small>  
> <small>[should respect max doc count in batch](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Ported/Subscriptions/SubscriptionsBasicTest.ts#L263) </small>  
> <small>[can disable subscription](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Ported/Subscriptions/SubscriptionsBasicTest.ts#L345) </small>  
> <small>[can delete subscription](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Ported/Subscriptions/SubscriptionsBasicTest.ts#L52) </small>

## Using object literals for entities

To comfortably use object literals as entities,  
configure the collection name that will be used in the store conventions.

This must be done *before* calling `initialize()` on the DocumentStore instance,  
else, your entities will be created in the *@empty* collection.

```javascript
const store = new DocumentStore(urls, database);

// Configure the collection name that will be used
store.conventions.findCollectionNameForObjectLiteral = entity => entity["collection"];
// ...
store.initialize();

// Sample object literal
const user = {
   collection: "Users",
   name: "John"
};

session = store.openSession();
await session.store(user);
await session.saveChanges();

// The document will be stored in the 'Users' collection
```

>##### Related tests:
> <small>[using object literals for entities](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/ReadmeSamples.ts#L644) </small>  
> <small>[using object literals](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/SessionApiTests.ts#L108) </small>  
> <small>[handle custom entity naming conventions + object literals](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Ported/BulkInsert/BulkInsertsTest.ts#L220) </small>

-->

## Using classes for entities

1. Define your model as class. Attributes should be just public properties:
```php
class Product {

    public ?string $id = null,
    public string $title = '',
    public int $price = 0,
    public string $currency = 'USD',
    public int $storage = 0,
    public string $manufacturer = '',
    public bool $in_stock = false,
    public ?DateTime $last_update = null

    public function __construct(
        $id = null,
        $title = '',
        $price = 0,
        $currency = 'USD',
        $storage = 0,
        $manufacturer = '',
        $in_stock = false,
        $last_update = null
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->price = $price;
        $this->currency = $currency;
        $this->storage = $storage;
        $this->manufacturer = $manufacturer;
        $this->in_stock = $in_stock;
        $this->last_update = $last_update ?? new DateTime();
    }
}
```

2. To store a document pass its instance to `store()`.  
   The collection name will automatically be detected from the entity's class name.
```php
use models\Product;

$product = new Product(
  null, 'iPhone X', 999.99, 'USD', 64, 'Apple', true, new Date('2017-10-01T00:00:00'));

$product = $session->store($product);

var_dump($product instanceof Product);                // true
var_dump(str_starts_with($product->id, 'products/')); // true

$session->saveChanges();
```

3. Loading a document
```php
$product = $session->load('products/1-A');
var_dump($product instanceof Product); // true
var_dump($product->id);                // products/1-A
```

4. Querying for documents
```php
$products = $session->query(Product::class)->toList();

foreach($products as $product) {
  var_dump($product instanceof Product);                // true
  var_dump(str_starts_with($product->id, 'products/')); // true
});
```  

<!-- >##### Related tests:  -->
> <!-- <small>[using classes](https://github.com/ravendb/ravendb-nodejs-client/blob/5c14565d0c307d22e134530c8d63b09dfddcfb5b/test/Documents/SessionApiTests.ts#L173) </small>  -->

## Usage with PHP

PHP typings are embedded into the package. Make sure to close session when you finish your work with it.

```php
// file models/product.php
class Product {
    public ?string $id = null,
    public string $title = '',
    public int $price = 0,
    public string $currency = 'USD',
    public int $storage = 0,
    public string $manufacturer = '',
    public bool $in_stock = false,
    public ?DateTime $last_update = null
    
    public function __construct(
        $id = null,
        $title = '',
        $price = 0,
        $currency = 'USD',
        $storage = 0,
        $manufacturer = '',
        $in_stock = false,
        $last_update = null
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->price = $price;
        $this->currency = $currency;
        $this->storage = $storage;
        $this->manufacturer = $manufacturer;
        $this->in_stock = $in_stock;
        $this->last_update = $last_update ?? new DateTime();
    }
}

// file app.php
use models\Product;
use RavenDB\Documents\DocumentStore;
use RavenDB\Documents\Session\DocumentSession;

$store = new DocumentStore('url', 'database name');
try {
    $store->initialize();
    
    $productId = null;
    
    /** @var DocumentSession $session */
    $session = $store->openSession();
    try {
        $product = new Product(
          null, 'iPhone X', 999.99, 'USD', 64, 'Apple', true, new Date('2017-10-01T00:00:00'));

        $session->store($product);
        $session->saveChanges();
        
        var_dump($product instanceof Product);                // true
        var_dump(str_starts_with($product->id, 'products/')); // true
        
        $productId = $product->id;
    } finally {
        $session->close();    
    }
    
    $session = $store->openSession();
    try {
        /** @var Product $product */
        $product = $session->load(Product::class, $productId);
        
        var_dump($product instanceof Product);                // true
        var_dump($product->id); // products/1-A
        
        /** @var array<Product> $products */
        $products = $session->query(Query::collection('Products'))
                    ->waitForNonStaleResults()
                    ->whereEquals('manufacturer', 'Apple')
                    ->whereEquals('in_stock', true)
                    ->whereBetween('last_update', new DateTime('- 1 week'), new DateTime())
                    ->whereGreaterThanOrEqual('storage', 64)
                    ->toList();
    
        foreach ($products as $product) {
            var_dump($product instanceof Product);                // true
            var_dump(str_starts_with($product->id, 'products/')); // true
        }
       
    } finally {
        $session->close();    
    }
    
} finally {
    $store->close();
}
```

## Working with a secure server

Your certificate and server certificate should be saved in PEM format to your machine.

1.  Create AuthOptions:

```php
$authOptions = AuthOptions::pem(
    '../clientCertPath.pem',
    'clientCertPass',
    '../serverCaCertPath.pem'
);
``` 


2. Pass auth options to `DocumentStore` object:

```php
$store = new DocumentStore('url', 'databaseName');
$store->setAuthOptions($authOptions); // use auth options to connect on database
$store->initialize();
```

## Running tests

Clone the repository:
```bash
git clone https://github.com/ravendb/ravendb-php-client
```

Install dependencies:
```bash
composer install
```
Run RavenDB server
```bash
https://a.phptest.development.run
```
Set environment variables.

```bash
# Set the following environment variables:
#
# - Certificate hostname
# RAVENDB_PHP_TEST_HTTPS_SERVER_URL=https://a.phptest.development.run
#
# RAVENDB_PHP_TEST_CA_PATH=
#
# - Certificate path for tests requiring a secure server:
# RAVENDB_PHP_TEST_CERTIFICATE_PATH=
#
# - Certificate for client
# RAVENDB_TEST_CLIENT_CERT_PATH=
# RAVENDB_TEST_CLIENT_CERT_PASSPHRASE=
#
# - For some tests, Developers licence is required in order to run them all 
# RAVEN_LICENSE=
```

Run PHPUnit
```bash
./vendor/bin/phpunit
```

-----
##### Bug Tracker
[http://issues.hibernatingrhinos.com/issues/RDBC](http://issues.hibernatingrhinos.com/issues/RDBC)

-----
##### License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
