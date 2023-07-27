<?php

namespace XP\VB\XF\Pub\Controller;

use XP\VB\XF\Entity\User;
use XF\Mvc\ParameterBag;

/**
 * Extends \XF\Pub\Controller\Member
 */
class Member extends XFCP_Member
{

    /**
     * @param \XP\VB\Entity\Request $request
     *
     * @return \XP\VB\Service\Request\Create
     */
    protected function setupRequestCreate(\XP\VB\Entity\Request $request)
    {
        /** @var \XP\VB\Service\Request\Create $createor */
        $createor = $this->service('XP\VB:Request\Create', $request);

        $createor->setMessage($this->plugin('XF:Editor')->fromInput('message'));

        $basicFields = $this->filter([]);
        $request->bulkSet($basicFields);

        if ($request->canUploadAndManageRequestAttachments())
        {
            $createor->setRequestAttachmentHash($this->filter('attachment_hash', 'str'));
        }

        return $createor;
    }

    public function actionRequest(ParameterBag $params)
    {
        $user = $this->assertViewableUser($params->user_id/*, $this->getUserViewExtraWith()*/);

        if (!$user->canRequestVb($error))
        {
            return $this->noPermission($error);
        }

        $request = $this->em()->create('XP\VB:Request');
        $request->user_id = $user->user_id;

        if ($this->isPost())
        {
            $createor = $this->setupRequestCreate($request);

            if (!$createor->validate($errors))
            {
                return $this->error($errors);
            }

            $createor->save();

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
                'user' => $user,
                'attachmentData' => $attachmentData,
            ];
            return $this->view('XF:Request\Request', 'xp_vb_user_request', $viewParams);
        }
    }

    public function actionRequestPreview(ParameterBag $params)
    {
        $this->assertPostOnly();

        $user = $this->assertViewableUser($params->user_id);

        $request = $this->em()->create('XP\VB:Request');
        $request->user_id = $user->user_id;

        $createor = $this->setupRequestCreate($request);

        if (!$createor->validate($errors))
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
}
