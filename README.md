# PHP-Query-Builder

[![pipeline status](https://gitlab.com/R1KO/php-query-builder/badges/master/pipeline.svg)](https://gitlab.com/R1KO/php-query-builder/-/commits/master) [![coverage report](https://gitlab.com/R1KO/php-query-builder/badges/master/coverage.svg)](https://gitlab.com/R1KO/php-query-builder/-/commits/master)


## Connection  with use QueryBuilder
```php
use R1KO\QueryBuilder\ConnectionWithBuilderFactory;

$params = [
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'port'      => '3306',
    'database'  => 'database',
    'username'  => 'root',
    'password'  => 'password',
    'charset'   => 'utf8mb4',
];

$db = ConnectionWithBuilderFactory::create($params);
```


## QueryBuilder

### Insert

> Inserts data into a table and returns the ID of the added row

```php
public function insert(array $values): int;
```

```php
$values = [
    'name'    => 'test',
    'email'   => 'test',
    'address' => 'test',
];
$id = $db->table('users')
    ->insert($values);
```


### Batch Insert

> Inserts a lot of data into a table and returns the number of row added

> Example: INSERT INTO (col, col, ...) VALUES (val, val, ...), (val, val, ...), ...;

```php
public function insertBatch(array $values): int;
```

```php
$values = [
    [
        'name'    => 'test',
        'email'   => 'test',
        'address' => 'test',
    ],
    [
        'name'    => 'test 2',
        'email'   => 'test 2',
        'address' => 'test 2',
    ],
];
$count = $db->table('users')
    ->insertBatch($values);
```


### Mass Insert

> Inserts a set of data into a table as a prepared query and returns an array of added row IDs

> Example: INSERT INTO (col, col, ...) VALUES (?, ?, ...);

```php
public function insertMass(array $values): array;
```

```php
$values = [
    [
        'name'    => 'test',
        'email'   => 'test',
        'address' => 'test',
    ],
    [
        'name'    => 'test 2',
        'email'   => 'test 2',
        'address' => 'test 2',
    ],
];
$ids = $db->table('users')
    ->insertMass($values);
```

```php
$schema = [
    'name',
    'email',
    'address',
];

$iterator = function (): iterable {
    // ...
    yield [
        'name'    => 'test 1',
        'email'   => 'test 2',
        'address' => 'test 3',
    ];
    // OR
    yield [
        'test 1',
        'test 2',
        'test 3',
    ];
};

$idsIterator = $db->table('users')
    ->insertIterable($schema, $iterator);
```

## Delete

> Deletes rows conditionally and returns the number of rows deleted

```php
$count = $db->table('users')
    ->where('status', 'outdated')
    ->delete();
```

## Update

> Update rows conditionally and returns the number of rows modified

```php
$count = $db->table('users')
    ->where('status', 'outdated')
    ->update(['status' => 'deleted']);
```


## Select

### `getAll`

> Gets an associative array of rows

```php
$id = $db->table('users')
    ->select(['id', 'status'])
    ->getAll();
```

```
[
    [
        'id' => 1,
        'status' => 'active',
    ],
    [
        'id' => 2,
        'status' => 'deleted',
    ],
]
```

### `getAssoc`

> Gets an associative array of row whose keys are the specified column

```php
$id = $db->table('users')
    ->select(['id', 'status'])
    ->getAssoc('id');
```

```
[
    4 => [
        'id' => 4,
        'status' => 'active',
    ],
    3 => [
        'id' => 3,
        'status' => 'deleted',
    ],
]
```

### `getIterable`

> > Get rows from the result one by one

```php
$users = $db->table('users')
    ->select(['id', 'status'])
    ->getIterable();

foreach ($users as $user) {
    
}
```

```
[
    'id' => 4,
    'status' => 'active',


[
    'id' => 3,
    'status' => 'deleted',
]

```


```php
$users = $db->table('users')
    ->select(['id', 'status'])
    ->getIterable('id');

foreach ($users as $user) {
    
}
```

```

4 => [
    'id' => 4,
    'status' => 'active',
]

3 => [
    'id' => 3,
    'status' => 'deleted',
]

```

### `getRow`

> Gets an associative array of one row

```php
$id = $db->table('users')
    ->select(['id', 'status'])
    ->getRow();
```

```
[
    'id' => 4,
    'status' => 'active',
]
```


### `getCol`

> Gets an array of rows of values of one column

```php
$id = $db->table('users')
    ->getCol('email');
```

```
[
    'test.user1@gmail.com',
    'ivan.ivanov@gmail.com',
    'petro.petrov@gmail.com',
]
```


### `getOne`

> Gets one value

```php
$id = $db->table('users')
    ->select('COUNT(amount)') // TODO
    ->getOne();
```

```
1500



### Aliases

```php
$id = $db->table('users')
    ->select(['id' => 'user_id', 'status'])
    ->getAll();
```

```
[
    [
        'user_id' => 1,
        'status' => 'active',
    ],
    [
        'user_id' => 2,
        'status' => 'deleted',
    ],
]
```

### `DISTINCT`

```php
$id = $db->table('users')
    ->select(['id', 'status'])
    ->distinct()
    ->getAll();
```

### Raw Column Expressions

```php
$columns = [
    'id',
    QueryBuilder::raw('address AS user_address'),
];
$results = $this->db->table('users')
    ->select($columns)
    ->getAll();
```

## Conditions

> TODO ...
> 
```php
public function where(string $column, array|string|int|bool $value);
public function where(string $column, Closure $value);
public function where(Closure $condition);
public function where(array $conditions);
public function whereAnd(array $conditions);
public function whereOr(array $conditions);
```

```php
$results = $this->db->table('users')
    ->select(['*'])
    ->where('name', $user['name'])
    ->getAll();
```

```php
$results = $this->db->table('users')
    ->select(['*'])
    ->where('name', $user['name'])
    ->where('email', $user['email'])
    ->getAll();
```

```php
$results = $this->db->table('users')
    ->select(['*'])
    ->where('name', $user['name'])
    ->orWhere('email', $user['email'])
    ->getAll();
```

```php
$results = $this->db->table('users')
    ->select(['*'])
    ->where('id', '>', 2) // TODO: remake this
    ->getAll();
```

```php
$results = $this->db->table('users')
    ->select(['*'])
    ->where('email', 'in', array_column($users, 'email')) // TODO: remake this
    ->getAll();
```

```php
$results = $this->db->table('users')
    ->select(['*'])
    ->where('email', $email)
    ->where(static function ($query) use ($users) {
        $addresses = array_column($users, 'address');
        $query->where('address', $addresses[1])
            ->orWhere('address', $addresses[2]);
    })
    ->getAll();
```

> SELECT * FROM users WHERE email = ? AND (address = ? OR address = ?)


```php
// TODO: 
->where('column', 'value') // column = 'value'
->where('column !=', 'value') // column != 'value'
->whereNot('column', 'value') // column != 'value'
->where('column >', 'value') // column > 'value'
->where('column <', 'value') // column < 'value'

->where('column IS', 'NULL') // column IS NULL
->where('column IS NOT', 'NULL') // column IS NOT NULL
->whereNull('column', 'NULL') // column IS NULL
->whereNotNull('column', 'NULL') // column IS NOT NULL

->where('column between', ['value_from', 'value_to']) // column BETWEEN 'value_from' AND 'value_to'
->where('column not between', ['value_from', 'value_to']) // column NOT BETWEEN 'value_from' AND 'value_to'
->whereBetween('column', ['value_from', 'value_to']) // column BETWEEN 'value_from' AND 'value_to'
->whereNotBetween('column', ['value_from', 'value_to']) // column NOT BETWEEN 'value_from' AND 'value_to'

->where('column in', ['value1', 'value2']) // column IN ('value1', 'value2')
->where('column not in', ['value1', 'value2']) // column IN ('value1', 'value2')
->whereIn('column', ['value1', 'value2']) // column IN ('value1', 'value2')
->whereNotIn('column', ['value1', 'value2']) // column IN ('value1', 'value2')

->where('column like', 'value1') // column LIKE 'value1'
->where('column not like', 'value1') // column NOT LIKE 'value1'
->where('column ilike', 'value1') // column ILIKE 'value1'
->whereLike('column like', 'value1') // column LIKE 'value1'
->whereNotLike('column not like', 'value1') // column NOT LIKE 'value1'
->whereIlike('column ilike', 'value1') // column ILIKE 'value1'


// TODO: JSON
// https://laravel.com/docs/8.x/queries#json-where-clauses

// whereRaw
// whereExists
// whereColumn

// chunk
// chunkById
```

## Limit & Offset

```php

$id = $db->table('users')
    ->limit(10)
    ->offset(5)
    ->getAll();
```

## Sorting

```php

$id = $db->table('users')
    ->orderBy('amount', 'DESC')
    ->getAll();
```

```php

$id = $db->table('users')
    ->orderAsc('amount')
    ->getAll();
```

```php

$id = $db->table('users')
    ->orderDesc('amount')
    ->getAll();
```

> TODO: orderByRaw

## Grouping

```php

$id = $db->table('users')
    ->groupBy('address')
    ->getAll();
```

```php

$id = $db->table('users')
    ->groupBy(['address'])
    ->getAll();
```

```php

$id = $db->table('users')
    ->groupBy('address', 'name')
    ->getAll();
```

## Having

> TODO ...


## Joins

> TODO ...

```php
$posts = $this->db->table('posts')
    ->select(['posts.*', 'users.name' => 'author_name'])
    ->join('users', ['posts.id_user' => 'users.id'])
    ->getAll();
```

```php
$posts = $this->db->table('posts')
    ->select(['posts.*', 'authors.name' => 'author_name'])
    ->join(['users' => 'authors'], ['posts.id_user' => 'authors.id'])
    ->getAll();
```

```php
$posts = $this->db->table('posts')
    ->select(['posts.*', 'authors.name' => 'author_name'])
    ->leftJoin(['users' => 'authors'], ['posts.id_user' => 'authors.id'])
    ->getAll();
```

```php
$posts = $this->db->table('posts')
    ->select(['posts.*', 'authors.name' => 'author_name'])
    ->rightJoin(['users' => 'authors'], ['posts.id_user' => 'authors.id'])
    ->getAll();
```

```php
$posts = $this->db->table('posts')
    ->select(['posts.*', 'authors.name' => 'author_name'])
    ->fullJoin(['users' => 'authors'], ['posts.id_user' => 'authors.id'])
    ->getAll();
```

// TODO: additional conditions

## Aggregate

> TODO ...

```php
$countCompletedOrders = $this->db->table('orders')
    ->where('status', 'completed')
    ->count();
```

```php
$countDeletedOrders = $this->db->table('orders')
    ->count('deleted_at');
```

```php
$countDeletedOrders = $this->db->table('orders')
    ->distinct()
    >count('id_product');
```


```php
$totalCompletedOrdersPrice = $this->db->table('orders')
    ->where('status', 'completed')
    ->sum('price');
```

```php
$averageCompletedOrdersPrice = $this->db->table('orders')
    ->where('status', 'completed')
    ->avg('price');
```

```php
$minCompletedOrdersPrice = $this->db->table('orders')
    ->where('status', 'completed')
    ->min('price');
```
```php
$maxCompletedOrdersPrice = $this->db->table('orders')
    ->where('status', 'completed')
    ->max('price');
```


## Raw Expressions

> TODO ...

