<?php

namespace XP\VB\Attachment;

use XF\Attachment\AbstractHandler;
use XF\Entity\Attachment;
use XF\Mvc\Entity\Entity;

class Request extends AbstractHandler
{
	public function canView(Attachment $attachment, Entity $container, &$error = null)
	{
		/** @var \XP\VB\Entity\Request $container */
		if (!$container->canView())
		{
			return false;
		}
		
		return $container->canViewRequestAttachments();
	}

	public function canManageAttachments(array $context, &$error = null)
	{
		$em = \XF::em();

		/** @var \XP\VB\XF\Entity\User $visitor */
		$visitor = \XF::visitor();
		
		if (!empty($context['request_id']))
		{
			/** @var \XP\VB\Entity\Request $request */
			$request = $em->find('XP\VB:Request', intval($context['request_id']));
			if (!$request || !$request->canView())
			{
				return false;
			}
		}
		else
		{
			// Uploading but not created a request yet.
			return ($visitor->hasVbRequestPermission('uploadRequestAttach'));
		}

		return $request->canUploadAndManageRequestAttachments();
	}

	public function onAttachmentDelete(Attachment $attachment, Entity $container = null)
	{
		if (!$container)
		{
			return;
		}

		/** @var \XP\VB\Entity\Request $container */
		$container->attach_count--;
		$container->save();
	}

	public function getConstraints(array $context)
	{
		return \XF::repository('XP\VB:Request')->getRequestAttachmentConstraints();
	}

	public function getContainerIdFromContext(array $context)
	{
		return isset($context['request_id']) ? intval($context['request_id']) : null;
	}

	public function getContainerLink(Entity $container, array $extraParams = [])
	{
		return \XF::app()->router('public')->buildLink('vb/requests', $container, $extraParams);
	}

	public function getContext(Entity $entity = null, array $extraContext = [])
	{
		if ($entity instanceof \XP\VB\Entity\Request)
		{
			$extraContext['request_id'] = $entity->request_id;
		}
		else
		{
			throw new \InvalidArgumentException("Entity must be a request");
		}

		return $extraContext;
	}
}