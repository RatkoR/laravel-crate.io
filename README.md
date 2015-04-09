## Crate.io driver for Laravel 5

This is an Eloquent and Query builder support for Crate.io. Extends 
the original Laravel API with Crate PDO driver.

###Crate and Crate PDO

Crate is a distributed SQL Database based on Elasticsearch, Lucene 
and other goodies. See their official page on [Crate.io](https://crate.io)
for more info.  

Crate.io published a [PDO](https://github.com/crate/crate-pdo) and
[DBAL](https://github.com/crate/crate-dbal) driver for easy access to
the crate DB server.  
Laravel-crate.io project uses those adapters when connecting to Crate DB.  

###Project status

Laravel-crate.io is used in our internal projects. We did a bunch of
unit tests and the driver seems ok.  

Still, it's still early in development, so **if you find any bugs, please
open a ticket in issues here**.

###Installation

Add a require to your composer.json  :

```json
{
    "require": {
        "ratkor/laravel-crate.io": "*"
    }
}
```

and run `composer update`

After composer downloads your package, you'll have to register this new Crate
DB provider in Laravel. Open `config/app.php` and add:

```php
'RatkoR\Crate\CrateServiceProvider',
```

to the list of `providers`.

####Crate.io server

You'll have to install crate.io server, of course. See installation
instructions on their site.

###Configuration

Open `config/database.php` and add new crate database connection (with
config values used in your crate setup):

```php
'crate' => array(
    'driver'   => 'crate',
    'host'     => 'localhost',
    'database' => 'doc',
    'port'     => 4200,
),
```

Next, change default database connection to `crate`.

```php
'default' => 'mongodb',
```

Lastly, change `fetch` PDO style to `FETCH_ASSOC` as crate does not suuport
default laravel FETCH_CLASS style:

```php
'fetch' => PDO::FETCH_ASSOC,
```


###What works and what doesn't

Crate.io supports many of the SQL statements, but not all of them. Many that
you're accustomed to might be missing, so be sure to take a look at their site
if you're in doubt.

We're throwing an `NotImplementedException` for those statements that you
might wrongly try to use. We tried to cover all of them, but if we missed any
you'll get Exception from Crate DB.

Big things that are **not** supported are:  
+ joins
+ subselects
+ auto increments
+ whereBetween
+ unique indexes
+ foreign keys (and relations)
+ dropping, renaming columns (adding fields works)
+ blobs (crate supports it, but we didn't implement it yet)

Crate specific stuff that was added is:
+ object type
+ array type
+ index off, index plain
+ fulltext indexes over single or multiple fields w/o analyzers


`Article::truncate()` has been changed to silently use `delete from article`;


###Schema support

Migration and schema are supported. You can use `artisan migrate` commands
to create or drop tables from crate.

Crate has only a subset of [field types](https://crate.io/docs/stable/sql/data_types.html)
(and some new ones), so choose appropriate.

Crate types:  
+ boolean
+ string
+ numeric (integer, long, short, double, float, byte)
+ ip, geo_point (still have to implement these two)
+ timestamp
+ object
+ array

Some SQL types are linked to crate types. For example, `bigInteger` is
linked to `long`, text is linked to `string`, `enum` to `string` also and so on.

An example of schema would be:  
```php
		Schema::create('article', function(Blueprint $table)
		{
			$table->integer('id');

			$table->string('title')->index('plain');
			$table->mediumText('summary');
			$table->text('internal_Comment')->index('off');
			$table->text('body')->index('fulltext:english');
			$table->long('nb_views');
			$table->timestamp('published_on');

			$table->arrayField('images','object as (id integer, title string');
			$table->objectField('author','(dynamic) as (id integer, name string)');

			$table->timestamps();

			$table->primary('id');
		});
```

Fulltext index on a single field can be added as:  
```php
$table->index('field1','fulltext');
```
or
```php
$table->string('field1')->index('fulltext');
```

Fulltext index on multiple fields:  
```php
$table->index(['field1','field2'],'fulltext');
```

Fulltext index with english analyzer on multiple fields:  
```php
$table->index(['field1','field2'],'fulltext:english');
```

Primary key on single field:  
```php
$table->primary('field1');
```

Primary key on multiple fields:  
```php
$table->primary(['f_id','f2_id']);
```

To not include a field in default index, use `->index('off')`:  
```php
$table->string('not_important_field')->index('off');
```

A PLAIN index (the default index) can be added as `->index('plain')`:  
```php
$table->string('field')->index('plain');
```
or
```php
$table->string('field')->index();
```
or just leave it out, crate will index it.

To drop a table in migration scripts:
```php
Schema::drop('article');
```

To add an object field use:
```php
$table->objectField('field_name', 'object parameters');
```
where `object parameters` can be any parameters that crate excepts for an object.
See their [documentation](https://crate.io/docs/stable/sql/data_types.html#object)
for objects. Examples would be:  
```php
$table->objectField('my_object_1','as (f_date timestamp)');
$table->objectField('my_object_2','as (object(dynamic) as (name string, birthday timestamp)');
```

Arrays are added with `->arrayField('name', 'array parameters')`. As is with
`object` type, `array paramters` can have any property that crate allows
for arrays. See their [documentation](https://crate.io/docs/stable/sql/data_types.html#array).
Examples for array of dynamic objects:  
```php
$table->arrayField('f_array','object as (age integer, name string');
```


###Basic usage

With crate DB connection, you can do simple and even more complex queries.
Some examples are written below:  

```php
$articles = DB::select('select * from article where id = ?', array(1));  

$user = DB::table('user')->where('email','some@example.com')->first();  

$users = DB::table('user')->get();
```


###Eloquent

To use Eloquent with Crate.io, you'll need to use Crate Eloquent model.

```php
use RatkoR\Crate\Eloquent\Model AS Eloquent;

class Article extends Eloquent {}
```

You can use (almost) all eloquent goodies as with the original eloquent model.

To use different table name, use:  
```php
protected $table = 'myArticles';
```
etc...

####Eloquent model alias

Instead of adding  
```php
use RatkoR\Crate\Eloquent\Model AS Eloquent;
```

to all your eloquent classes, you can add an alias to `alias` array in `config/app.php`:
```php
'CrateEloquent' => 'RatkoR\Crate\Eloquent\Model'
```

This will allow you to shorten the class definition to:
```php
class Article extends CrateEloquent {}
```

####Eloquent usage

It can be used mostly the same as an original Laravel eloquent model. 

#####Getting all users:  
```php
$articles = Article::all();
```

#####Getting by primary key:  
```php
$article = Article::find(1);
```

#####Using where(s):  
```php
$articles = Article::where('name','LIKE','Star%')->where('views','>',100)->get();
```

#####Using limits(s):  
```php
$articles = Article::where('name','LIKE','Star%')->take(10)->get();
```

#####Using whereIn:  
```php
$articles = Article::whereIn('id',[1,2,3])->get();
```

#####Using select for fields:  
```php
$article = Article::select('id','name')->where('id',1)->first();
```

#####Using count:  
```php
$nb = Article::where('views','>',100)->count();
```

#####Inserting
```php
$new = Article::create([
	'id' => 1, // don't forget, there is no auto increment
	'title' => 'John Doe and friends',
	'summary' => '...'
]);
```

#####Updating
```php
$article = Article::find(1);

$article->title = 'Brand new title';
$article->save();
```

#####Deleting
```php
$article = Article::find(1);
$article->delete();
```
