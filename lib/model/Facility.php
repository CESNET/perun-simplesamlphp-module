<?php
/**
 * Created by PhpStorm.
 * User: pavel
 * Date: 9.4.18
 * Time: 9:43
 */
class sspmod_perun_model_Facility implements sspmod_perun_model_HasId
{
	private $id;
	private $name;
	private $entityId;

	/**
	 * sspmod_perun_model_Vo constructor.
	 * @param int $id
	 * @param string $name
	 * @param string $shortName
	 */
	public function __construct($id, $name, $entityId)
	{
		$this->id = $id;
		$this->name = $name;
		$this->entityId = $entityId;
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
	public function getEntityId()
	{
		return $this->entityId;
	}

}