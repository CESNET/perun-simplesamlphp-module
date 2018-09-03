<?php

/**
 * @author Ondrej Velisek <ondrejvelisek@gmail.com>
 */
class sspmod_perun_model_Group implements sspmod_perun_model_HasId
{
	private $id;
	private $voId;
	private $name;
	private $uniqueName;
	private $description;

	/**
	 * sspmod_perun_model_Group constructor.
	 * @param $id
	 * @param $voId
	 * @param $name
	 * @param $uniqueName
	 * @param $description
	 */
	public function __construct($id, $voId, $name, $uniqueName, $description)
	{
		$this->id = $id;
		$this->voId = $voId;
		$this->name = $name;
		$this->uniqueName = $uniqueName;
		$this->description = $description;
	}


	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return int
	 */
	public function getVoId()
	{
		return $this->voId;
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
	public function getUniqueName()
	{
		return $this->uniqueName;
	}



	/**
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}




}