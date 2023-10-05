<?php

namespace Dxw\Whippet\Models;

// Responsible for wrapping data about dxw inspections in an object
class Inspection
{
	/**
	 * @psalm-suppress PossiblyUnusedProperty
	 */
	public $date;
	/**
	 * @psalm-suppress PossiblyUnusedProperty
	 */
	public $versions;
	/**
	 * @psalm-suppress PossiblyUnusedProperty
	 */
	public $result;
	/**
	 * @psalm-suppress PossiblyUnusedProperty
	 */
	public $url;

	public function __construct($date_string, $versions, $result, $url)
	{
		$this->date = date_create($date_string);
		$this->versions = $versions;
		$this->result = $result;
		$this->url = $url;
	}
}
