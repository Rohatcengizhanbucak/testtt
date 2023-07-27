<?php

namespace XP\VB\Pub\Controller;

use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class Request extends AbstractController
{
	protected function preDispatchController($action, ParameterBag $params)
	{
		/** @var \XP\VB\XF\Entity\User $visitor */
		$visitor = \XF::visitor();
	}

	public function actionIndex(ParameterBag $params)
	{
		if ($params->request_id)
		{
			return $this->rerouteController(__CLASS__, 'view', $params);
		}

		/** @var \XP\VB\ControllerPlugin\RequestList $requestListPlugin */
		$requestListPlugin = $this->plugin('XP\VB:RequestList');

		$listParams = $requestListPlugin->getRequestListData();
		
		$this->assertValidPage($listParams['page'], $listParams['perPage'], $listParams['total'], 'vb/requests');
		$this->assertCanonicalUrl($this->buildLink('vb/requests', null, ['page' => $listParams['page']]));

		$viewParams = $listParams;

		return $this->view('XP\VB:RequestsIndex', 'xp_vb_request_index', $viewParams);
	}

	public function actionFilters()
	{
		/** @var \XP\VB\ControllerPlugin\RequestList $requestListPlugin */
		$requestListPlugin = $this->plugin('XP\VB:RequestList');

		return $requestListPlugin->actionFilters();
	}

	public function actionView(ParameterBag $params)
	{
		$request = $this->assertViewableRequest($params->request_id);
		
		$this->assertCanonicalUrl($this->buildLink('vb/requests', $request));
		
		$viewParams = [
			'request' => $request,
			'user' => $request->User,
		];
		return $this->view('XP\VB:Request\View', 'xp_vb_request_view', $viewParams);
	}

	/**
	 * @param \XP\VB\Entity\Request $request
	 *
	 * @return \XP\VB\Service\User\Edit
	 */
	protected function setupRequestEdit(\XP\VB\Entity\Request $request)
	{
		/** @var \XP\VB\Service\Request\Edit $editor */
		$editor = $this->service('XP\VB:Request\Edit', $request);
		
		$editor->setMessage($this->plugin('XF:Editor')->fromInput('message'));
		
		if ($request->canManage())
		{
			$editor->setStatusMessage($this->plugin('XF:Editor')->fromInput('status_message'));
		}

		$request->edit_date = time();
		
		if ($request->canUploadAndManageRequestAttachments())
		{
			$editor->setAttachmentHash($this->filter('attachment_hash', 'str'));
		}
		
		if ($this->filter('author_alert', 'bool'))
		{
			$editor->setSendAlert(true, $this->filter('author_alert_reason', 'str'));
		}

		return $editor;
	}

	public function actionEdit(ParameterBag $params)
	{
		$request = $this->assertViewableRequest($params->request_id);
		if (!$request->canEdit($error))
		{
			return $this->noPermission($error);
		}

		if ($this->isPost())
		{
			$editor = $this->setupRequestEdit($request);

			if (!$editor->validate($errors))
			{
				return $this->error($errors);
			}

			$editor->save();

			return $this->redirect($this->buildLink('vb/requests', $request));
		}
		else
		{
			if ($request->canUploadAndManageRequestAttachments())
			{
				/** @var \XF\Repository\Attachment $attachmentRepo */
				$attachmentRepo = $this->repository('XF:Attachment');
				$attachmentData = $attachmentRepo->getEditorData('vb_request', $request);
			}
			else
			{
				$attachmentData = null;
			}
			
			$viewParams = [
				'request' => $request,
				'user' => $request->User,
				'attachmentData' => $attachmentData,
			];
			return $this->view('XF:Request\Edit', 'xp_vb_request_edit', $viewParams);
		}
	}

	public function actionPreview(ParameterBag $params)
	{
		$this->assertPostOnly();

		$request = $this->assertViewableRequest($params->request_id);
		if (!$request->canEdit($error))
		{
			return $this->noPermission($error);
		}

		$editor = $this->setupRequestEdit($request);

		if (!$editor->validate($errors))
		{
			return $this->error($errors);
		}

		$attachments = [];
		$tempHash = $this->filter('attachment_hash', 'str');

		if ($request->canUploadAndManageRequestAttachments())
		{
			/** @var \XF\Repository\Attachment $attachmentRepo */
			$attachmentRepo = $this->repository('XF:Attachment');
			$attachmentData = $attachmentRepo->getEditorData('vb_request', $request, $tempHash);
			$attachments = $attachmentData['attachments'];
		}

		return $this->plugin('XF:BbCodePreview')->actionPreview(
			$request->message, 'vb_request', $request->User, $attachments, $request->canViewRequestAttachments()
		);
	}

	/**
	 * @param \XP\VB\Entity\Request $request
	 *
	 * @return \XP\VB\Service\Request\Approve
	 */
	protected function setupRequestApprove(\XP\VB\Entity\Request $request)
	{
		/** @var \XP\VB\Service\Request\Approve $approver */
		$approver = $this->service('XP\VB:Request\Approve', $request);
	
		$approver->setStatus();
		$approver->setStatusMessage($this->plugin('XF:Editor')->fromInput('status_message'));
		
		$approver->setSendAlert(true);
	
		return $approver;
	}
	
	public function actionApprove(ParameterBag $params)
	{
		$request = $this->assertViewableRequest($params->request_id);
		if (!$request->canApprove($error))
		{
			return $this->noPermission($error);
		}
	
		if ($this->isPost())
		{
			$approver = $this->setupRequestApprove($request);
			
			if (!$approver->validate($errors))
			{
				return $this->error($errors);
			}
			
			$approver->save();
			
			return $this->redirect($this->buildLink('vb/requests', $request));
		}
		else
		{
			$viewParams = [
				'request' => $request,
				'user' => $request->User
			];
			return $this->view('XF:Request\Approve', 'xp_vb_request_approve', $viewParams);
		}
	}


    /**
     * @param \XP\VB\Entity\Request $request
     *
     * @return \XP\VB\Service\Request\Close
     */
    protected function setupRequestClose(\XP\VB\Entity\Request $request)
    {
        /** @var \XP\VB\Service\Request\Close $closer */
        $closer = $this->service('XP\VB:Request\Close', $request);

        $closer->setStatus();
        $closer->setStatusMessage($this->plugin('XF:Editor')->fromInput('status_message'));

        $closer->setSendAlert(true);

        return $closer;
    }

    public function actionClose(ParameterBag $params)
    {
        $request = $this->assertViewableRequest($params->request_id);
        if (!$request->canClose($error))
        {
            return $this->noPermission($error);
        }

        if ($this->isPost())
        {
            $closer = $this->setupRequestClose($request);

            if (!$closer->validate($errors))
            {
                return $this->error($errors);
            }

            $closer->save();

            return $this->redirect($this->buildLink('vb/requests', $request));
        }
        else
        {
            $viewParams = [
                'request' => $request,
                'user' => $request->User
            ];
            return $this->view('XF:Request\Close', 'xp_vb_request_close', $viewParams);
        }
    }
	
	/**
	 * @param \XP\VB\Entity\Request $request
	 *
	 * @return \XP\VB\Service\Request\Reject
	 */
	protected function setupRequestReject(\XP\VB\Entity\Request $request)
	{
		/** @var \XP\VB\Service\Request\Reject $rejector */
		$rejector = $this->service('XP\VB:Request\Reject', $request);

		$rejector->setStatus();
		$rejector->setStatusMessage($this->plugin('XF:Editor')->fromInput('status_message'));

		if ($this->filter('author_alert', 'bool'))
		{
			$rejector->setSendAlert(true, $this->filter('author_alert_reason', 'str'));
		}
	
		return $rejector;
	}
	
	public function actionReject(ParameterBag $params)
	{
		$request = $this->assertViewableRequest($params->request_id);
		if (!$request->canReject($error))
		{
			return $this->noPermission($error);
		}
	
		if ($this->isPost())
		{
			$rejector = $this->setupRequestReject($request);
			
			if (!$rejector->validate($errors))
			{
				return $this->error($errors);
			}
			
			$rejector->save();
			
			return $this->redirect($this->buildLink('vb/requests', $request));
		}
		else
		{
			$viewParams = [
				'request' => $request,
				'user' => $request->User
			];
			return $this->view('XF:Request\Reject', 'xp_vb_request_reject', $viewParams);
		}
	}
	
	public function actionIp(ParameterBag $params)
	{
		$request = $this->assertViewableRequest($params->request_id);
		$breadcrumbs = $request->getBreadcrumbs();
	
		/** @var \XF\ControllerPlugin\Ip $ipPlugin */
		$ipPlugin = $this->plugin('XF:Ip');
		return $ipPlugin->actionIp($request, $breadcrumbs);
	}
	
	/**
	 * @param $requestId
	 * @param array $extraWith
	 *
	 * @return \XP\VB\Entity\Request
	 *
	 * @throws \XF\Mvc\Reply\Exception
	 */
	protected function assertViewableRequest($requestId, array $extraWith = [])
	{
		$visitor = \XF::visitor();

		$extraWith[] = 'User';

		/** @var \XP\VB\Entity\Request $request */
		$request = $this->em()->find('XP\VB:Request', $requestId, $extraWith);
		if (!$request)
		{
			throw $this->exception($this->notFound(\XF::phrase('xp_vb_requested_request_not_found')));
		}

		if (!$request->canView($error))
		{
			throw $this->exception($this->noPermission($error));
		}

		return $request;
	}

	/**
	 * @return \XP\VB\Repository\Request
	 */
	protected function getRequestRepo()
	{
		return $this->repository('XP\VB:Request');
	}
}