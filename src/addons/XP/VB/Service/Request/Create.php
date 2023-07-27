<?php

namespace XP\VB\Service\Request;

use XP\VB\Entity\Request;

class Create extends \XF\Service\AbstractService
{
	use \XF\Service\ValidateAndSavableTrait;

	/**
	 * @var \XP\VB\Entity\Request
	 */
	protected $request;

	/**
	 * @var \XP\VB\Service\Request\Preparer
	 */
	protected $requestPreparer;

	protected $performValidations = true;

	public function __construct(\XF\App $app, Request $request)
	{
		parent::__construct($app);
		$this->request = $this->setUpRequest($request);
	}

	protected function setupRequest(Request $request)
	{
		$this->request = $request;

		$this->requestPreparer = $this->service('XP\VB:Request\Preparer', $this->request);

		$visitor = \XF::visitor();
		$this->request->user_id = $visitor->user_id;
		$this->request->username = $visitor->username;
		
		return $request;
	}

	public function getRequest()
	{
		return $this->request;
	}

	public function setPerformValidations($perform)
	{
		$this->performValidations = (bool)$perform;
	}

	public function getPerformValidations()
	{
		return $this->performValidations;
	}

	public function setIsAutomated()
	{
		$this->logIp(false);
		$this->setPerformValidations(false);
	}

	public function setMessage($message, $format = true)
	{
		$this->requestPreparer->setMessage($message, $format, $this->performValidations);
	}

	public function setRequestAttachmentHash($hash)
	{
		$this->requestPreparer->setAttachmentHash($hash);
	}

	public function logIp($logIp)
	{
		$this->requestPreparer->logIp($logIp);
	}

	protected function finalSetup()
	{
	}

	protected function _validate()
	{
		$this->finalSetup();

		$request = $this->request;

		if (!$request->user_id)
		{
			/** @var \XF\Validator\Username $validator */
			$validator = $this->app->validator('Username');
			$request->username = $validator->coerceValue($request->username);

			if ($this->performValidations && !$validator->isValid($request->username, $error))
			{
				return [
					$validator->getPrintableErrorValue($error)
				];
			}
		}

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

		$this->requestPreparer->afterInsert();

		$db->commit();

		return $request;
	}

	public function sendNotifications()
	{
		if ($this->request->isVisible())
		{
			/** @var \XP\VB\Service\Request\Notify $notifier */
			$notifier = $this->service('XP\VB:Request\Notify', $this->request, 'request');
			$notifier->setMentionedUserIds($this->requestPreparer->getMentionedUserIds());
			$notifier->notifyAndEnqueue(3);
		}
	}
}