<?php

namespace XP\VB\Repository;

use XF\Mvc\Entity\ArrayCollection;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;
use XF\PrintableException;
use XF\Util\Arr;

class Request extends Repository
{
	public function findRequestsForRequestList(array $limits = [])
	{
		/** @var \XP\VB\Finder\Request $requestFinder */
		$requestFinder = $this->finder('XP\VB:Request');

        $requestFinder
			->forFullView() // or ->forFullView(true)
			->useDefaultOrder();

		return $requestFinder;
	}

	public function getRequestAttachmentConstraints()
	{
		$options = $this->options();
	
		return [
			'extensions' => Arr::stringToArray($options->xpVbAllowedFileExtensions), //preg_split('/\s+/', trim($options->xpVbAllowedFileExtensions), -1, PREG_SPLIT_NO_EMPTY),
			'size' => $options->xpVbAttachmentMaxFileSize * 1024,
			'width' => $options->attachmentMaxDimensions['width'],
			'height' => $options->attachmentMaxDimensions['height'],
			'count' =>$options->xpVbAttachmentMaxPerRequest
        ];
	}	

	public function sendModeratorActionAlert(
		\XP\VB\Entity\Request $request, $action, $reason = '', array $extra = [], \XF\Entity\User $forceUser = null
	)
	{
		if (!$forceUser)
		{
			if (!$request->user_id || !$request->User)
			{
				return false;
			}

			$forceUser = $request->User;
		}

		$extra = array_merge([
			'username' => $request->User->username,
			'link' => $this->app()->router('public')->buildLink('nopath:vb/requests', $request),
			'userLink' => $this->app()->router('public')->buildLink('nopath:members', $request->User),
			'reason' => $reason
		], $extra);

		/** @var \XF\Repository\UserAlert $alertRepo */
		$alertRepo = $this->repository('XF:UserAlert');
		$alertRepo->alert(
			$forceUser,
			0, '',
			'user', $forceUser->user_id,
			"vb_request_{$action}", $extra
		);

		return true;
	}

	public function getUserRequestCount($userId)
	{
		return $this->db()->fetchOne("
			SELECT COUNT(*)
			FROM xf_xp_vb_request
			WHERE user_id = ?
		", $userId);
	}
}