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
unit tests and the driver seems ok. We use id as a caching layer in front
of our DB servers. Crate is insanely fast (Elasticsearch) and offloads our
DB servers a lot.  

Still, it's still early in development, so **if you find any bugs, please
open an issue ticket**.

###Installation

Add a require to your composer.json  :

```json
{
    "require": {
        "ratkor/laravel-crate.io": "*"
    }
}
```

and run `composer update`.

After composer downloads your package, you'll have to register this new Crate
DB provider in Laravel. Open `config/app.php` and add:

```php
'RatkoR\Crate\CrateServiceProvider',
```

to the list of `providers`.

Laravel 5.1 users can add:

```php
RatkoR\Crate\CrateServiceProvider::class,
```

You'll have to install crate.io server, of course. See installation
instructions on their site.


###Configuration

Open `config/database.php` and add new crate database connection (with
config values to match your setup):

```php
'crate' => array(
    'driver'   => 'crate',
    'host'     => 'localhost',
    'database' => 'doc',
    'port'     => 4200,
),
```

Next, change default database connection to `"crate"`.

```php
'default' => 'crate',
```

####Configuration for multiple hosts

This driver can handle conenctions to multiple crate hosts. To use them, write
`host` config parameter as a comma delimited list of hosts. Like:

```php
'crate' => array(
    'driver'   => 'crate',
    'host'     => 'localhost,10.0.0.1,10.0.0.2',
    'database' => 'doc',
    'port'     => 4200,
),
```

The DSN created in this case looks like:
```
'crate:localhost:4200,10.0.0.1:4200,10.0.0.2:4200'
```

If you need to specify different ports, add them to the `host` param like:

```php
    'host'     => 'localhost:4201,10.0.0.1:4300,10.0.0.2',
```

which will create a DSN like:
```
'crate:localhost:4201,10.0.0.1:4300,10.0.0.2:4200'
```

**Randomization**

`crate-pdo` takes [the first host](https://github.com/crate/crate-pdo/blob/master/src/Crate/PDO/PDO.php#L87)
from list of hosts. To overcome this we randomize all hosts
so that connections to multiple crate servers are distributed. If you don't want
randomization, add a `randomHosts` parameter and set it to `false`:

```php
'crate' => array(
    'driver'   => 'crate',
    'host'     => 'localhost,10.0.0.1,10.0.0.2',
    'database' => 'doc',
    'port'     => 4200,
    'randomHosts' => false,
),
```


**Note on PDO::FETCH_CLASS**  

Crate doesn't support PDO::FETCH_CLASS so we silently change it to PDO::FETCH_ASSOC.
This happens only for Crate connections, your normal MySQL connections will still use FETCH_CLASS.

See more about fetch types on their [github](https://github.com/crate/crate-pdo).


###What works and what doesn't

Crate.io supports many of the SQL statements, but not all of them. Be sure to
take a look at their [site](https://crate.io/docs/stable/sql/index.html) if you're in doubt.

We're throwing an `RatkoR\Crate\NotImplementedException` for those statements that you
might wrongly try to use. We tried to cover all of them, but if we missed any
you'll get Exception from Crate DB.

Big things that are **not** supported are:  
+ joins
+ subselects
+ [auto increments](https://crate.io/docs/stable/sql/ddl.html#constraints) - you'll have to manage those by yourself
+ whereBetween(s)
+ unique indexes
+ foreign keys (and relations)
+ dropping, renaming columns (adding fields works)
+ naming columns like _ id, _ version, _ score - these are restricted, crate uses it [internally](https://crate.io/docs/stable/sql/ddl.html)

Crate specific stuff that was added is:
+ object type
+ array type
+ index off, index plain
+ fulltext indexes over single or multiple fields w/o analyzers


Also, `Article::truncate()` has been changed to silently use `delete from article`;

Note, that Crate.io does not support uppercase letters in table or schema names.
See this and other restrictions [here](https://crate.io/docs/stable/sql/ddl.html#naming-restrictions).

###Schema support

Migration and schema are supported. You can use `artisan migrate` commands
to create or drop tables.

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

Some SQL types are silently linked to crate types. For example, `bigInteger` is
linked to `long`, `text, mediumtext, longtext, enum` are linked to `string`, ...

An example of schema in migration file would be:  
```php
        Schema::create('article', function(Blueprint $table)
        {
            $table->integer('id');

            $table->string('title')->index('plain');
            $table->mediumText('summary');
            $table->text('internal_Comment')->index('off');
            $table->text('body')->index('fulltext:english');
            $table->bigInteger('nb_views');
            $table->timestamp('published_on');

            $table->arrayField('images','object as (id integer, title string');
            $table->objectField('author','(dynamic) as (id integer, name string)');

            $table->timestamps();

            $table->primary('id');
        });
```

####Blob tables

Creating (and dropping) blob tables is also supported. Blob tables don't have arbitrary
colums, just digest and last_moified. And even these are created automatically.

An example of create blob schema is:
```php
        Schema::createBlob('myblob');
```

*There is no need for the callback parameter (the second parameter in createBlob() which
defines fields). If you pass it it will be silently ignored.*

To drop a table in schema do:

```php
    Schema::dropBlob('myblob');
```


####Description of some SQL/Crate schema differences

**Fulltext index on a single field can be added as:**  
```php
$table->index('field1','fulltext');
```
or
```php
$table->string('field1')->index('fulltext');
```

**Fulltext index on multiple fields:**  
```php
$table->index(['field1','field2'],'fulltext');
```

**Fulltext index with english analyzer on multiple fields:**  
```php
$table->index(['field1','field2'],'fulltext:english');
```

**Primary key on single field:**  
```php
$table->primary('field1');
```

**Primary key on multiple fields:**  
```php
$table->primary(['f_id','f2_id']);
```

**To not include a field in default index**
```php
$table->string('not_important_field')->index('off');
```

**A PLAIN index (the default index)**
```php
$table->string('field')->index('plain');
```
or
```php
$table->string('field')->index();
```
or just leave it out, crate will index it.

**To drop a table in migration scripts:**
```php
Schema::drop('article');
```

**To add an 'object' field use:**  

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

**Add an 'array' field:**  

Arrays are added with `->arrayField('name', 'array parameters')`. As is with
`object` type, `array paramters` can have any property that crate allows
for arrays. See their [documentation](https://crate.io/docs/stable/sql/data_types.html#array).
Examples for array of dynamic objects:  
```php
$table->arrayField('f_array','object as (age integer, name string');
```


###Basic usage

With crate DB connection, you can do simple and even more complex queries.
Some examples are:  

```php
$articles = DB::select('select * from article where id = ?', array(1));  

$user = DB::table('user')->where('email','some@example.com')->first();  

$users = DB::table('user')->get();
```


###Eloquent

To use Eloquent you'll need to use Crate Eloquent model.

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
use CrateEloquent;
class Article extends CrateEloquent {}
```

####Eloquent usage

It can be used mostly the same as an original Laravel eloquent model.

#####Getting all articles:  
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

#####Complex where(s):  
```php
$articles = Article::where('id','=',3)->orWhere(function($query)
            {
                $query->where('title', 'Star Wars 7')
                      ->orWhere('title', 'none');
            })->get();
```

*etc...*  


#####Inserting
```php
$new = Article::create([
    'id' => 1, // don't forget, there is no auto increment
    'title' => 'John Doe and friends',
    'summary' => '...',
    'array_of_strings' => ['one', 'two'],
    'object_field' => ['author' => 'Someone', 'title' => 'Editpr']
]);
```

#####Updating
```php
$article = Article::find(1);

$article->title = 'Brand new title';
$article->array_of_strings = ['tree', 'four'];
$article->object_field = ['author' => 'Someone Else', 'title' => 'Administrator'];
$article->save();
```

*Note*: when you update array or object field, whatever is in that field will be
replaced with whatever you give. You cannot append or change just one value.

```php
$article->object_field = ['crated_by' => 'Third Person'];
```

would *not* append 'created_by' field to the fields that are already existing, but
would overwrite and leave only 'created_by' value in 'object_field'. To fix this,
do an update like:

```php
$newValues = $article->object_field;
$newValues['created_by'] = 'Third Person';

$article->object_field = $newValues;
```

#####Deleting
```php
$article = Article::find(1);
$article->delete();
```

###Tests

There are two kinds of tests:  
+ Lexical tests
+ Data tests


###Lexical tests

Lexical tests check if SQL statements produced
by Query builder are semantically correct.

These tests are executed relatively fast. They check that all common
SQLs are unaffected by code changes.

###Data tests

Data tests connect to real Crate.io server and try to manage data there.
Selecting, inserting, updating, deleting queries, all are tested.  

These tests take longer to finish. We found that queriying for a record immediatelly
after it has been inserted can produce negative results. Crate
needs some time between insert (or delete, or update) requests and
all next selects that query for this changes. So we have couple of
`sleep(1)` statements in test code.

Running data tests for the first time will probably fail as `migration`
table will not exist yet. Try rerunning test and it will proceed ok.

Conenction properties for tests are in `tests/DataTests/Config/database.php`
file and can be changed for your setup.  

Data tests will create:  
+ `t_migration` table for test table migrations,
+ `t_users` table for some dummy user data.
