<?php

namespace XP\VB\Pub\Controller;

use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\AbstractReply;
use XF\Mvc\Reply\View;

abstract class AbstractController extends \XF\Pub\Controller\AbstractController
{
	protected function preDispatchController($action, ParameterBag $params)
	{
		/** @var \XP\VB\XF\Entity\User $visitor */
		$visitor = \XF::visitor();
	}

	protected function postDispatchController($action, ParameterBag $params, AbstractReply &$reply)
	{

	}
	
	/**
	 * @param $userId
	 * @param array $extraWith
	 *
	 * @return \XP\VB\Entity\User
	 *
	 * @throws \XF\Mvc\Reply\Exception
	 */
	protected function assertViewableUser($userId, array $extraWith = [])
	{
		$visitor = \XF::visitor();

		$extraWith[] = 'User';

		/** @var \XP\VB\Entity\User $user */
		$user = $this->em()->find('XP\VB:User', $userId, $extraWith);
		if (!$user)
		{
			throw $this->exception($this->notFound(\XF::phrase('xp_vb_requested_user_not_found')));
		}

		if (!$user->canView($error))
		{
			throw $this->exception($this->noPermission($error));
		}

		return $user;
	}	


	/**
	 * @return \XP\VB\Repository\User
	 */
	protected function getUserRepo()
	{
		return $this->repository('XP\VB:User');
	}

}