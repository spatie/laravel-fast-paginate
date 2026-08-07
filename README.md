# A fast limit/offset paginator for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-fast-paginate.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-fast-paginate)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/spatie/laravel-fast-paginate/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/spatie/laravel-fast-paginate/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/spatie/laravel-fast-paginate/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/spatie/laravel-fast-paginate/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-fast-paginate.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-fast-paginate)

Paginating a large table with `limit` and `offset` gets slower with every page. To return page 10 000, your database first has to read every row that comes before it, including all the columns you selected.

This package adds a `fastPaginate` method that does the same job in two steps. It first runs your query with only the primary key selected, so the database can satisfy the whole thing from an index, and then fetches the full rows for the keys on that page.

```php
// Instead of this...
User::query()->where('active', true)->paginate();

// ...do this.
User::query()->where('active', true)->fastPaginate();
```

The method signature is identical to Laravel's own `paginate`, and you get back the same paginator, so nothing else in your application has to change.

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/laravel-fast-paginate.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/laravel-fast-paginate)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require spatie/laravel-fast-paginate
```

That's all. The package registers itself, and the `fastPaginate` method becomes available on every query builder and relation.

## Usage

Anywhere you would call `paginate`, you can call `fastPaginate` instead.

```php
User::query()->fastPaginate();

User::query()->where('active', true)->orderBy('name')->fastPaginate(perPage: 50);
```

There's a counterpart for `simplePaginate` too.

```php
User::query()->simpleFastPaginate();
```

Relations are supported as well.

```php
$user->posts()->fastPaginate();

$user->roles()->simpleFastPaginate();
```

If you use [Laravel Scout](https://laravel.com/docs/scout), both methods are available on search builders. Scout already fetches results by key, so these calls are handed straight to Scout's own paginators. They exist so that you don't have to think about which paginator to reach for.

```php
User::search('john')->fastPaginate();
```

### How it works

Rather than asking the database for full rows at an offset, this package runs a query that only touches the primary key.

```sql
select id from users order by id limit 15 offset 150000
```

Because that query only reads one column, the database can often answer it from an index alone, without visiting the table. The keys that come back are then used to fetch the rows you actually asked for.

```sql
select * from users where id in (150001, 150002, ...)
```

This is a variation on a technique called a deferred join. Aaron Francis wrote [an in-depth explanation of the theory behind it](https://aaronfrancis.com/2022/efficient-pagination-using-deferred-joins).

The speedup depends on your data, your indexes, and how deep into the result set your users go. It's unlikely that this approach performs worse than a plain `limit` and `offset`, but it is possible, so measure it against your own data.

### Queries that can't be optimized

Some queries can't be split into two steps, because a single row of the inner query no longer maps to a single primary key. When `fastPaginate` sees one of these, it quietly hands the query to Laravel's regular paginator, so it's always safe to call.

That happens when your query has a `group by`, a `having`, or a `union`, when it selects a bound expression that is also used in the order, or when you pass `-1` as the page size to fetch every record.

### Ordering

Without an explicit order, a database is free to return rows in any order it likes, which would let the two queries disagree about what belongs on the current page. So when your query has no order, this package adds one on the primary key.

If you do order your results, that order is applied to both queries. Any column you select and then order by, such as an alias added by `withCount`, is kept on the inner query so the sort still works.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

This package is a port of [aaronfrancis/fast-paginate](https://github.com/aarondfrancis/fast-paginate) by [Aaron Francis](https://aaronfrancis.com), who came up with the approach and wrote the original implementation. All credit for the idea, and for most of the code in this package, goes to him.

- [Aaron Francis](https://github.com/aarondfrancis)
- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
