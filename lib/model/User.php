<?php

/**
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */
class sspmod_perun_model_User implements sspmod_perun_model_HasId
{
	private $id;
	private $name;

	/**
	 * sspmod_perun_model_User constructor.
	 * @param int $id
	 * @param string $name
	 */
	public function __construct($id, $name)
	{
		$this->id = $id;
		$this->name = $name;
	}

	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}


}