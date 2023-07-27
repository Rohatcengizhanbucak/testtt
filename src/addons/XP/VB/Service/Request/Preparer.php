<?php

namespace XP\VB\Service\Request;

use XP\VB\Entity\Request;

class Preparer extends \XF\Service\AbstractService
{
	/**
	 * @var Request
	 */
	protected $request;

	protected $attachmentHash;

	protected $logIp = true;

	protected $mentionedUsers = [];

	public function __construct(\XF\App $app, Request $request)
	{
		parent::__construct($app);
		$this->setRequest($request);
	}
	
	public function setRequest(Request $request)
	{
		$this->request = $request;
	}

	public function getRequest()
	{
		return $this->request;
	}

	public function logIp($logIp)
	{
		$this->logIp = $logIp;
	}

	public function getMentionedUsers($limitPermissions = true)
	{
		if ($limitPermissions)
		{
			/** @var \XF\Entity\User $user */
			$user = $this->request->User ?: $this->repository('XF:User')->getGuestUser();
			return $user->getAllowedUserMentions($this->mentionedUsers);
		}
		else
		{
			return $this->mentionedUsers;
		}
	}

	public function getMentionedUserIds($limitPermissions = true)
	{
		return array_keys($this->getMentionedUsers($limitPermissions));
	}
	
	public function setMessage($message, $format = true, $checkValidity = true)
	{
		$preparer = $this->getMessagePreparer($format);
		$this->request->message = $preparer->prepare($message, $checkValidity);
		$this->request->embed_metadata = $preparer->getEmbedMetadata();

		$this->mentionedUsers = $preparer->getMentionedUsers();

		return $preparer->pushEntityErrorIfInvalid($this->request);
	}

	/**
	 * @param bool $format
	 *
	 * @return \XF\Service\Message\Preparer
	 */
	protected function getMessagePreparer($format = true)
	{
		$options = $this->app->options();

		$maxImages = 100;
		$maxMedia = 30;

		/** @var \XF\Service\Message\Preparer $preparer */
		$preparer = $this->service('XF:Message\Preparer', 'vb_request', $this->request);
		$preparer->setConstraint('maxLength', $options->messageMaxLength);
		$preparer->setConstraint('maxImages', $maxImages);
		$preparer->setConstraint('maxMedia', $maxMedia);

		if (!$format)
		{
			$preparer->disableAllFilters();
		}

		return $preparer;
	}
	
	public function setStatusMessage($statusMessage, $format = true, $checkValidity = true)
	{
		$preparer = $this->getStatusMessagePreparer($format);
		$this->request->status_message = $preparer->prepare($statusMessage, $checkValidity);
	
		return $preparer->pushEntityErrorIfInvalid($this->request);
	}
	
	/**
	 * @param bool $format
	 *
	 * @return \XF\Service\Message\Preparer
	 */
	protected function getStatusMessagePreparer($format = true)
	{
		$options = $this->app->options();
	
		$maxImages = 100; //10
		$maxMedia = 30; //3
	
		/** @var \XF\Service\Message\Preparer $preparer */
		$preparer = $this->service('XF:Message\Preparer', 'vb_request', $this->request);
		$preparer->setConstraint('maxLength', $options->messageMaxLength);
		$preparer->setConstraint('maxImages', $maxImages);
		$preparer->setConstraint('maxMedia', $maxMedia);
	
		if (!$format)
		{
			$preparer->disableAllFilters();
		}
	
		$preparer->setConstraint('allowEmpty', true);
		
		return $preparer;
	}

	public function setAttachmentHash($hash)
	{
		$this->attachmentHash = $hash;
	}

	public function validateFiles(&$error = null)
	{
		$request = $this->request;
	
		return true;
	}
	
	public function afterInsert()
	{
		if ($this->attachmentHash)
		{
			$this->associateAttachments($this->attachmentHash);
		}

		if ($this->logIp)
		{
			$ip = ($this->logIp === true ? $this->app->request()->getIp() : $this->logIp);
			$this->writeIpLog($ip);
		}
	}

	public function afterUpdate()
	{
		if ($this->attachmentHash)
		{
			$this->associateAttachments($this->attachmentHash);
		}
	}
	
	protected function associateAttachments($hash)
	{
		$request = $this->request;
	
		/** @var \XF\Service\Attachment\Preparer $inserter */
		$inserter = $this->service('XF:Attachment\Preparer');
		$associated = $inserter->associateAttachmentsWithContent($hash, 'vb_request', $request->request_id);
	
		if ($associated)
		{
			$request->fastUpdate('attach_count', $request->attach_count + $associated);
		}
	}	

	protected function writeIpLog($ip)
	{
		$request = $this->request;

		/** @var \XF\Repository\IP $ipRepo */
		$ipRepo = $this->repository('XF:Ip');
		$ipEnt = $ipRepo->logIp($request->user_id, $ip, 'vb_request', $request->request_id);
		if ($ipEnt)
		{
			$request->fastUpdate('ip_id', $ipEnt->ip_id);
		}
	}
}