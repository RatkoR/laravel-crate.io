<?php

namespace DataTests;

use DataTests\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use StdClass;

class DataTest extends TestCase {

    protected static $testNb = 0;

    protected function setUp(): void
    {
        parent::setUp();

        static::$testNb++;

        $this->app->singleton('migration.repository', function ($app) {
            $table = $app['config']['database.migrations'];

            return new \DataTests\Fixture\DatabaseMigrationRepository($app['db'], $table);
        });

        if (static::$testNb === 2) {
            sleep(1);
        }

        Artisan::call('migrate', [
            '--database' => 'crate',
            '--path' => '../../../../tests/DataTests/Migrations'
        ]);

        // Sanity check. This will fail if migrations failed.
        User::truncate();
        DB::table('t_users')->get();
    }

    /** @test */
    public function it_adds_a_new_user()
    {
        $class = new stdClass();
        $class->param1 = 1;
        $class->param2 = "two";

        $user = User::create([
            'id' => 1,
            'name' => 'User 1',
            'email' => 'user1@example.com',
            'f_array' => ['one','two','three','four'],
            'f_object' => $class,
        ]);

        $this->forceCrateConsistency();

        $this->assertInstanceOf('DataTests\Models\User', $user);
        $this->assertEquals(true, $user->exists);
        $this->assertEquals(1, (int)$user->id);
        $this->assertEquals('User 1', $user->name);
        $this->assertEquals(1, User::count());
        $this->assertInstanceOf('Carbon\Carbon', $user->created_at);
        $this->assertEquals(true, is_array($user->f_array));
        $this->assertEquals('one', $user->f_array[0]);
        $this->assertEquals(4, count($user->f_array));
        $this->assertEquals(true, is_object($user->f_object));
        $this->assertEquals(1, $user->f_object->param1);
        $this->assertEquals('two', $user->f_object->param2);
    }

    /** @test */
    public function it_adds_a_multiple_users()
    {
        User::create(['id'=>2,'name'=>'User 2','email'=>'user2@example.com']);
        User::create(['id'=>3,'name'=>'User 3','email'=>'user3@example.com']);
        User::create(['id'=>4,'name'=>'User 4','email'=>'user4@example.com']);
        User::create(['id'=>5,'name'=>'User 5','email'=>'user5@example.com']);
        $user = User::create(['id'=>6,'name'=>'User 6','email'=>'user6@example.com']);

        $this->forceCrateConsistency();

        $this->assertInstanceOf('DataTests\Models\User', $user);
        $this->assertEquals(true, $user->exists);
        $this->assertEquals('User 6', $user->name);
        $this->assertEquals(6, $user->id);
        $this->assertEquals(5, User::count());
    }

    /** @test */
    public function it_finds_user_by_name()
    {
        User::create(['id'=>1,'name'=>'User 1','email'=>'user1@example.com']);
        User::create(['id'=>2,'name'=>'User 2','email'=>'user2@example.com']);

        $this->forceCrateConsistency();

        $user = User::where('name', 'User 1')->first();

        $this->assertInstanceOf('DataTests\Models\User', $user);
        $this->assertEquals(true, $user->exists);
        $this->assertEquals(1, $user->id);
    }

    /** @test */
    public function it_finds_user_by_id()
    {
        User::create(['id'=>1,'name'=>'User 1','email'=>'user1@example.com']);
        User::create(['id'=>2,'name'=>'User 2','email'=>'user2@example.com']);

        $this->forceCrateConsistency();

        $user = User::find(2);

        $this->assertInstanceOf('DataTests\Models\User', $user);
        $this->assertEquals(true, $user->exists);
        $this->assertEquals(2, $user->id);
    }

    /** @test */
    public function it_updates_user()
    {
        User::create(['id'=>1,'name'=>'User 1','email'=>'user1@example.com']);
        User::create(['id'=>2,'name'=>'User 2','email'=>'user2@example.com','f_array'=>['one','two'], 'f_object' => ['subkey'=>'sub-key','subname'=>'sub-name']]);

        $this->forceCrateConsistency();
        sleep(1);

        $user = User::find(2);
        $user->name = 'User X';
        $user->f_array = ['three', 'four'];
        $user->f_object = ['subkey'=>'new-sub-key', 'subname' => 'new-sub-name'];
        $user->save();

        $this->assertInstanceOf('DataTests\Models\User', $user);
        $this->assertEquals(true, $user->exists);
        $this->assertEquals('User X', $user->name);

        $this->forceCrateConsistency();

        $user = User::find(2);

        $this->assertInstanceOf('DataTests\Models\User', $user);
        $this->assertEquals(true, $user->exists);
        $this->assertEquals('User X', $user->name);
        $this->assertEquals(['three', 'four'], $user->f_array);
        $this->assertEquals('new-sub-name', $user->f_object['subname']);
        $this->assertEquals(['subkey'=>'new-sub-key','subname' => 'new-sub-name'], $user->f_object);

        $this->assertInstanceOf('Carbon\Carbon', $user->created_at);
        $this->assertInstanceOf('Carbon\Carbon', $user->updated_at);
        $this->assertNotEquals($user->created_at->timestamp, $user->updated_at->timestamp);
    }

    /** @test */
    public function it_finds_all_users()
    {
        User::create(['id'=>1,'name'=>'User 1','email'=>'user1@example.com']);
        User::create(['id'=>2,'name'=>'User 2','email'=>'user2@example.com']);

        $this->forceCrateConsistency();

        $users = User::all();

        $this->assertEquals(2, count($users));
        $this->assertContains('User 1', $users->pluck('name'));
        $this->assertContains('User 2', $users->pluck('name'));
    }

    /** @test */
    public function it_deletes_one_user()
    {
        User::create(['id'=>1,'name'=>'User 1','email'=>'user1@example.com']);
        User::create(['id'=>2,'name'=>'User 2','email'=>'user2@example.com']);

        $this->forceCrateConsistency();

        $this->assertEquals(2, User::count());

        $user = User::find(2);
        $user->delete();

        $this->forceCrateConsistency();

        $this->assertEquals(1, User::count());
    }

    /** @test */
    public function it_fetches_nonexisting_user()
    {
        $user = User::where('name','nonexistent')->first();
        $this->assertEquals(null, $user);

        $users = User::where('name','nonexistent')->get();
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $users);
        $this->assertEquals(0, count($users));
    }

    /** @test */
    public function it_checks_user_to_array()
    {
        User::create(['id'=>1,'name'=>'User 1','email'=>'user1@example.com']);

        $this->forceCrateConsistency();

        $user = User::find(1);
        $array = $user->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('password', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
        $this->assertTrue(is_string($array['created_at']));
        $this->assertTrue(is_string($array['updated_at']));
        $this->assertTrue(is_string($array['name']));
    }

    /** @test */
    public function it_selects_users_by_fields()
    {
        User::create(['id'=>2,'name'=>'User a 2','email'=>'user2@example.com']);
        User::create(['id'=>3,'name'=>'User a 3','email'=>'user3@example.com']);
        User::create(['id'=>4,'name'=>'User b 4','email'=>'user4@example.com']);
        User::create(['id'=>5,'name'=>'User b 5','email'=>'user5@example.com']);
        User::create(['id'=>6,'name'=>'User b 6','email'=>'user6@example.com']);

        $this->forceCrateConsistency();

        $this->assertEquals(5, User::count());

        $users = User::where('id',3)->orWhere('id',4)->get();
        $this->assertEquals(2, count($users), 'Test: id=3 || id=4');

        $users = User::where('id',3)->where('id',4)->get();
        $this->assertEquals(0, count($users), 'Test: id=3 && id=4');

        $users = User::where('id','>',2)->where('id','<',6)->get();
        $this->assertEquals(3, count($users), 'Test: id>2 && id<6');

        $users = User::where('name','LIKE','User a%')->get();
        $this->assertEquals(2, count($users), 'Test: LIKE User a%');

        $users = User::where('name','LIKE','%b%')->get();
        $this->assertEquals(3, count($users), 'Test: LIKE %b%');

        $user = User::select('name')->where('name','User b 5')->first();
        $this->assertEquals('User b 5', $user->name, 'Test: select name -> name exists');
        $this->assertEquals(null, $user->email, 'Test: select name -> email is null');

        $users = User::whereIn('id',[3,6])->get();
        $this->assertEquals(2, count($users), 'Test: IN 3,6');

        $users = User::whereNotIn('id',[3,6])->get();
        $this->assertEquals(3, count($users), 'Test: NOT IN 3,6');

        $count = User::where('id', '<>', 3)->count();
        $this->assertEquals(4, $count, 'Test: count');

        $users = User::where('id','=',3)->orWhere(function($query)
            {
                $query->where('name', 'User b 6')
                      ->orWhere('name', 'none');
            })->get();
        $this->assertEquals(2, count($users), 'Test: subquery');
    }

    /** @test */
    public function it_tests_pagination()
    {
        User::create(['id'=>2,'name'=>'User a 2','email'=>'user2@example.com']);
        User::create(['id'=>3,'name'=>'User a 3','email'=>'user3@example.com']);
        User::create(['id'=>4,'name'=>'User b 4','email'=>'user4@example.com']);
        User::create(['id'=>5,'name'=>'User b 5','email'=>'user5@example.com']);
        User::create(['id'=>6,'name'=>'User b 6','email'=>'user6@example.com']);

        $this->forceCrateConsistency();

        $this->assertEquals(5, User::count());

        $results = User::paginate(2);
        $this->assertEquals(2, $results->count());
        $this->assertNotNull($results->first()->name);
        $this->assertEquals(5, $results->total());
    }

    /** @test */
    public function it_tests_prepared_statements_with_params()
    {
        User::create(['id'=>2,'name'=>'User a 2','email'=>'user2@example.com']);
        User::create(['id'=>3,'name'=>'User a 3','email'=>'user3@example.com']);

        $this->forceCrateConsistency();

        $this->assertEquals(2, User::count());

        $user_2 = DB::select('select * from t_users where id = ?', array(2));
        $user_3 = DB::select('select * from t_users where id = ?', array(3));

        $this->assertEquals(2, $user_2[0]->id);
        $this->assertEquals(3, $user_3[0]->id);
        $this->assertEquals('user2@example.com', $user_2[0]->email);
        $this->assertEquals('user3@example.com', $user_3[0]->email);
    }

    /** @test */
    public function it_tests_prepared_statements_without_params()
    {
        User::create(['id'=>2,'name'=>'User a 2','email'=>'user2@example.com']);
        User::create(['id'=>3,'name'=>'User a 3','email'=>'user3@example.com']);

        $this->forceCrateConsistency();

        $this->assertEquals(2, User::count());

        $user_2 = DB::select('select * from t_users where id = 2');
        $user_3 = DB::select('select * from t_users where id = 3');

        $this->assertEquals(2, $user_2[0]->id);
        $this->assertEquals(3, $user_3[0]->id);
        $this->assertEquals('user2@example.com', $user_2[0]->email);
        $this->assertEquals('user3@example.com', $user_3[0]->email);
    }

    /**
     * Disabled test.
     *
     * Before crate-pdo 1.1 we had an error saving object to a string field. Seems
     * that crate now allows it. I don't know if we should treat this as a
     * regression or feature...
     *
     * public function it_throws_meaningful_error()
     * {
     *     $foo = new stdClass();
     *     $foo->bar = 'test';
     *
     *     $this->expectException(\RatkoR\Crate\QueryException::class);
     *     $this->expectExceptionMessage('Cannot cast {"bar"=\'test\'} to type string');
     *
     *     $user = User::create(['id'=>1,'name'=> $foo,'email'=>'user1@example.com']);
     * }
     */
}
