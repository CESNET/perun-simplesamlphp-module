<?php

/**
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */
class sspmod_perun_model_Group implements sspmod_perun_model_HasId
{
	private $id;
	private $name;
	private $description;

	/**
	 * sspmod_perun_model_Group constructor.
	 * @param $id
	 * @param $name
	 * @param $description
	 */
	public function __construct($id, $name, $description)
	{
		$this->id = $id;
		$this->name = $name;
		$this->description = $description;
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

	/**
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}




}