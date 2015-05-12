<?php namespace DataTests\Models;

use RatkoR\Crate\Eloquent\Model AS Eloquent;

class User extends Eloquent
{
	protected $table = 't_users';
	
	protected $fillable = ['id', 'name', 'email', 'password', 'f_array', 'f_object'];
}
