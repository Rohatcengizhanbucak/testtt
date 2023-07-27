<?php

namespace XP\VB\Service\Request;

use XP\VB\Entity\Request;

class Reject extends \XF\Service\AbstractService
{
	use \XF\Service\ValidateAndSavableTrait;

	/**
	 * @var Request
	 */
	protected $request;
	
	/**
	 * @var \XP\VB\Service\Request\Preparer
	 */
	protected $requestPreparer;

	protected $performValidations = true;

	protected $alert = false;
	protected $alertReason = '';

	public function __construct(\XF\App $app, Request $request)
	{
		parent::__construct($app);
		$this->setRequest($request);
	}

	public function setRequest(Request $request)
	{
		$this->request = $request;
		$this->requestPreparer = $this->service('XP\VB:Request\Preparer', $this->request);
	}

	public function getRequest()
	{
		return $this->request;
	}
	
	public function getRequestPreparer()
	{
		return $this->requestPreparer;
	}
	
	public function setPerformValidations($perform)
	{
		$this->performValidations = (bool)$perform;
	}
	
	public function getPerformValidations()
	{
		return $this->performValidations;
	}

	public function setStatus()
	{
		$this->request->request_status = 'rejected';
		$this->request->reject_date = \XF::$time;
	}
	
	public function setStatusMessage($statusMessage, $format = true)
	{
		return $this->requestPreparer->setStatusMessage($statusMessage, $format);
	}
	
	public function setSendAlert($alert, $reason = null)
	{
		$this->alert = (bool)$alert;
		if ($reason !== null)
		{
			$this->alertReason = $reason;
		}
	}

	protected function _validate()
	{
		$request = $this->request;

		$request->preSave();
		$errors = $request->getErrors();

		if ($this->performValidations)
		{
		}

		return $errors;
	}

	protected function _save()
	{
		$request = $this->request;
		
		$db = $this->db();
		$db->beginTransaction();
		
		$request->save(true, false);
		
		$this->requestPreparer->afterUpdate();

		if ($this->alert && $request->user_id != \XF::visitor()->user_id)
		{
			/** @var \XP\VB\Repository\Request $requestRepo */
			$requestRepo = $this->repository('XP\VB:Request');
			$requestRepo->sendModeratorActionAlert($request, 'reject', $this->alertReason);
		}
		
		$db->commit();
		
		return $request;
	}
}