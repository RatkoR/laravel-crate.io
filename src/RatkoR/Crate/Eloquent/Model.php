<?php namespace RatkoR\Crate\Eloquent;

use Illuminate\Database\Eloquent\Model as BaseModel;

class Model extends BaseModel 
{
	/**
	* Crate.io does not have self incrementing fields,
	* just plain integers. You have to set primary keys on
	* your own.
	*/
	public $incrementing = false;

	/**
	* created_at, updated_at and similar fields are
	* timespamp fields, not datetime.
	*/
	protected function getDateFormat()
	{
		return 'U';
	}
}