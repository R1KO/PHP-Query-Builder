# PHP Query Builder

[![pipeline status](https://gitlab.com/R1KO/php-query-builder/badges/master/pipeline.svg)](https://gitlab.com/R1KO/php-query-builder/-/commits/master) [![coverage report](https://gitlab.com/R1KO/php-query-builder/badges/master/coverage.svg)](https://gitlab.com/R1KO/php-query-builder/-/commits/master)

## Connection with use QueryBuilder

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

```php
public function insert(array $values): int;
```

> Insert data into a table and returns the ID of the added row

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

```php
public function insertBatch(array $values): int;
```

Insert a lot of data into a table and returns the number of row added

Example SQL:
```sql
INSERT INTO (col1, col2, ...colN) VALUES (val1, val2, ...valN), (val1, val2, ...valN), ...;
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

```php
public function insertMass(array $values, bool $useTransaction = false): array;
```

Insert a set of data into a table as a prepared query and returns an array of added row IDs

Example SQL:
```sql
INSERT INTO (col1, col2, ...colN) VALUES (?, ?, ...);
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

### Iterable Insert

```php
public function insertIterable(array $schema, iterable $iterator, bool $useTransaction = false): iterable;
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

Delete rows conditionally and returns the number of rows deleted

```php
$count = $db->table('users')
    ->where('status', 'outdated')
    ->delete();
```

## Update

Update rows conditionally and returns the number of rows modified

```php
$count = $db->table('users')
    ->where('status', 'outdated')
    ->update(['status' => 'deleted']);
```


## Select

### `getAll`

Gets an associative array of rows

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

Gets an associative array of row whose keys are the specified column
If column is null - assoc by first column of select

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

Get rows from the result one by one

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
]

[
    'id' => 3,
    'status' => 'deleted',
]

```

Get rows from the result one by one with associated key

```php
$users = $db->table('users')
    ->select(['id', 'status'])
    ->getIterable('id');

foreach ($users as $id => $user) {
    
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

Gets an associative array of one row

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
$emails = $db->table('users')
    ->getCol('email');
```

```
[
    'test.user1@gmail.com',
    'ivan.ivanov@gmail.com',
    'petro.petrov@gmail.com',
]
```

### `getColIterable`

```php
$emails = $db->table('users')
    ->getColIterable('email');

foreach ($emails as $email) {
    
}
```

```

'test.user1@gmail.com'

'ivan.ivanov@gmail.com'

'petro.petrov@gmail.com'

```


### `getOne`

> Gets one value

```php
$id = $db->table('users')
    ->select($db->raw('COUNT(amount)'))
    ->getOne();
```


### Aliases

```php
$id = $db->table('users')
    ->select([
        'id' => 'user_id', 
        'status', 
        $db->raw('IF(deleted_at IS NULL, 1, 0)') => 'is_active'
    ])
    ->getAll();
```

```
[
    [
        'user_id' => 1,
        'status' => 'active',
        'is_active' => 1,
    ],
    [
        'user_id' => 2,
        'status' => 'inactive',
        'is_active' => 0,
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
    $db->raw('address AS user_address'),
    // or
    QueryBuilder::asRaw('address AS user_address'),
    // TODO: subquery
];
$results = $db->table('users')
    ->select($columns)
    ->getAll();
```

## Conditions

> TODO ...
> 
```php
public function where(string $column, array|string|int|bool $value);
public function where(string $column, string $operator, array|string|int|bool $value);
public function where(string $column, Closure $value);
// WHERE column operator (condition|subquery)

public function where(Closure $condition);
// WHERE (condition)

public function where(Raw $expression);

public function where(array $conditions);
// WHERE [condition 1] AND [condition 2] AND [condition N] ...
public function whereAnd(array $conditions);
// WHERE [condition 1] AND [condition 2] AND [condition N] ...
public function whereOr(array $conditions);
// WHERE [condition 1] OR [condition 2] OR [condition N] ...

// TODO
public function whereColumn
public function whereExists

upsert
increment
decrement


```

```php
$results = $this->db->table('users')
    ->select(['*'])
    ->where('name', 'R1KO')
    ->getAll();
```

```php
$results = $this->db->table('users')
    ->select(['*'])
    ->where('name', 'R1KO')
    ->where('email', 'vova.andrienko@mail.ru')
    ->getAll();
```

```php
$results = $this->db->table('users')
    ->select(['*'])
    ->where('name', 'R1KO')
    ->orWhere('email', 'vova.andrienko@mail.ru')
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

```sql
SELECT * FROM users WHERE email = ? AND (address = ? OR address = ?)
```

#### Comparison Operators

##### Equals
```php
->where('column', 'value') // column = 'value'
->where('column !=', 'value') // column != 'value'

// TODO: 
->whereNot('column', 'value') // column != 'value'
```

##### Comparison
```php
->where('column >', 'value') // column > 'value'
->where('column <', 'value') // column < 'value'
->where('column >=', 'value') // column >= 'value'
->where('column <=', 'value') // column < ='value'

// TODO: 
->whereGreater('column >', 'value') // column > 'value'
->whereLess('column', 'value') // column < 'value'
->whereGreaterOrEqual('column >', 'value') // column >= 'value'
->whereLessOrEqual('column', 'value') // column <= 'value'
```

##### NULL
```php
->where('column IS', 'NULL') // column IS NULL
->where('column IS NOT', 'NULL') // column IS NOT NULL

// TODO: 
->whereNull('column', 'NULL') // column IS NULL
->whereNotNull('column', 'NULL') // column IS NOT NULL
```

##### BETWEEN
```php
->where('column between', ['value_from', 'value_to']) // column BETWEEN 'value_from' AND 'value_to'
->where('column not between', ['value_from', 'value_to']) // column NOT BETWEEN 'value_from' AND 'value_to'

// TODO: 
->whereBetween('column', ['value_from', 'value_to']) // column BETWEEN 'value_from' AND 'value_to'
->whereNotBetween('column', ['value_from', 'value_to']) // column NOT BETWEEN 'value_from' AND 'value_to'
```

##### IN
```php
->where('column in', ['value1', 'value2']) // column IN ('value1', 'value2')
->where('column not in', ['value1', 'value2']) // column IN ('value1', 'value2')

// TODO:
->whereIn('column', ['value1', 'value2']) // column IN ('value1', 'value2')
->whereNotIn('column', ['value1', 'value2']) // column IN ('value1', 'value2')
```

##### LIKE
```php
->where('column like', 'value1') // column LIKE 'value1'
->where('column not like', 'value1') // column NOT LIKE 'value1'
->where('column ilike', 'value1') // column ILIKE 'value1'

// TODO: 
->whereLike('column like', 'value1') // column LIKE 'value1'
->whereNotLike('column not like', 'value1') // column NOT LIKE 'value1'
->whereIlike('column ilike', 'value1') // column ILIKE 'value1'

```

#### JSON
```php
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

```php

// TODO: orderByRaw
```

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

```php
$db->raw('COUNT(amount)')
$db->raw('IF(deleted_at IS NULL, 1, 0)')
QueryBuilder::asRaw('address AS user_address'),
$db->builder()->raw('address AS user_address'),
```

