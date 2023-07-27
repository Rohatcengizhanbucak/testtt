<?php

namespace XP\VB\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int|null request_id
 * @property int user_id
 * @property string username
 * @property int request_date
 * @property int edit_date
 * @property int approve_date
 * @property int reject_date
 * @property int close_date
 * @property string request_status
 * @property string message
 * @property string status_message
 * @property int attach_count
 * @property int ip_id
 * @property array|null embed_metadata
 *
 * GETTERS
 *
 * RELATIONS
 * @property \XF\Entity\Attachment[] Attachments
 * @property \XF\Entity\User User
 * @property \XF\Entity\DeletionLog DeletionLog
 */

class Request extends Entity implements \XF\BbCode\RenderableContentInterface
{
	public function getBreadcrumbs($includeSelf = true)
	{
		$breadcrumbs[] = [
			'href' => $this->app()->router()->buildLink('vb/requests', $this),
			'value' => $this->User->username
		];

		return $breadcrumbs;
	}

	public function canView(&$error = null)
	{
		$visitor = \XF::visitor();
		if (!$visitor->user_id)
		{
			return false;
		}

		if ($this->hasPermission('manageAnyRequest'))
		{
			return true;
		}

		return (
			$this->user_id == $visitor->user_id
			&& $this->hasPermission('viewOwnRequest')
		);
	}
	
	public function canUploadAndManageRequestAttachments()
	{
		return $this->hasPermission('uploadRequestAttach');
	}

	public function canViewRequestAttachments()
	{
		$visitor = \XF::visitor();
		
		if ($this->hasPermission('manageAnyRequest'))
		{
			return true;
		}

		return (
			$this->user_id == $visitor->user_id
			&& $this->hasPermission('viewOwnRequest')
		);
	}
	
	public function canManage(&$error = null)
	{
		$visitor = \XF::visitor();
		if (!$visitor->user_id)
		{
			return false;
		}
	
		return $this->hasPermission('manageAnyRequest');
	}

	public function canEdit(&$error = null)
	{
		$visitor = \XF::visitor();
		if (!$visitor->user_id)
		{
			return false;
		}

		if ($this->hasPermission('manageAnyRequest'))
		{
			return true;
		}

		return (
			$this->user_id == $visitor->user_id
			&& $this->hasPermission('editOwnRequest')
		);
	}

	public function canApprove(&$error = null)
	{
		if ($this->request_status != 'pending')
		{
			return false;
		}
		
		return (
			\XF::visitor()->user_id
			&& $this->hasPermission('manageAnyRequest')
		);
	}
	
	public function canReject(&$error = null)
	{
		if ($this->request_status != 'pending')
		{
			return false;
		}
		
		return (
			\XF::visitor()->user_id
			&& $this->hasPermission('manageAnyRequest')
		);
	}

    public function canClose(&$error = null)
    {
        if ($this->request_status != 'pending')
        {
            return false;
        }

        return (
            \XF::visitor()->user_id
            && $this->hasPermission('manageAnyRequest')
        );
    }

	public function hasPermission($permission)
	{
		/** @var \XP\VB\XF\Entity\User $visitor */
		$visitor = \XF::visitor();
		return $visitor->hasVbRequestPermission($permission);
	}
	
	public function isIgnored()
	{
		return \XF::visitor()->isIgnoring($this->user_id);
	}

	public function isPending()
	{
		return ($this->request_status == 'pending');
	}
	
	public function isApproved()
	{
		return ($this->request_status == 'approved');
	}
	
	public function isRejected()
	{
		return ($this->request_status == 'rejected');
	}

    public function isClosed()
    {
        return ($this->request_status == 'closed');
    }
	
	public function isAttachmentEmbedded($attachmentId)
	{
		if (!$this->embed_metadata)
		{
			return false;
		}
	
		if ($attachmentId instanceof \XF\Entity\Attachment)
		{
			$attachmentId = $attachmentId->attachment_id;
		}
	
		return isset($this->embed_metadata['attachments'][$attachmentId]);
	}	
	
	public function hasImageAttachments($request = false)
	{
		if (!$this->attach_count)
		{
			return false;
		}
		
		if ($request && $request['Attachments'])
		{
			$attachments = $request['Attachments'];
		}
		else 
		{
			$attachments = $this->Attachments;
		}	
		
		foreach ($attachments AS $attachment)
		{
			if ($attachment['thumbnail_url'])
			{
				return true;
			}
		}
		
		return false;
	}
	

	public function rebuildCounters()
	{
		return true;
	}
	
	public function getBbCodeRenderOptions($context, $type)
	{
		return [
			'entity' => $this,
			'user' => $this->User,
			'attachments' => $this->attach_count ? $this->Attachments : [],
			'viewAttachments' => $this->canViewRequestAttachments()
		];
	}	

	protected function _preSave()
	{
	}

	protected function _postSave()
	{

		// TODO implement post save stuff

		if ($this->isUpdate() && $this->isChanged('request_status'))
		{
			if ($this->request_status == 'approved')
			{
                $this->closeUsersOtherPendingRequests();
                $this->giveUserBadge();
			}
			elseif ($this->request_status == 'rejected')
			{
                $this->removeUserBadge();
			}
            elseif ($this->request_status == 'closed')
            {
                if (!$this->User)
                {
                    return;
                }

                $user = $this->User;
                $user->save();

                return;
            }
		}
        if (!$this->isUpdate()){
            if (!$this->User)
            {
                return;
            }

            $user = $this->User;
            $user->xp_vb_verificationrequest_count = $this->setUserRequestCount($this->user_id);
            $user->save();
        }
	}

    protected function setUserRequestCount($userId)
    {
        return $this->db()->fetchOne("
			SELECT COUNT(*)
			FROM xf_xp_vb_request
			WHERE user_id = ?
		", $userId);
    }

    protected function closeUsersOtherPendingRequests()
    {
        $pendingRequestIds = $this->db()->fetchAllColumn("
			SELECT request_id
			FROM xf_xp_vb_request
			WHERE user_id = ?
				AND request_id != ?
				AND request_status = 'pending'
		", [$this->user_id, $this->request_id]);

        if ($pendingRequestIds)
        {
            foreach ($pendingRequestIds AS $pendingRequestId)
            {
                $pendingRequest = $this->app()->find('XP\VB:Request', $pendingRequestId);
                if (!$pendingRequest)
                {
                    continue;
                }

                $pendingRequest->request_status = 'closed';
                $pendingRequest->close_date = time();
                $pendingRequest->status_message = $pendingRequest->status_message . " \n\n" . \XF::phrase('xp_vb_your_request_has_been_automatically_closed');
                $pendingRequest->save();
            }
        }
    }

    protected function giveUserBadge()
    {
        if (!$this->User)
        {
            return;
        }


        $user = $this->User;
        //$user->userRequestedVb($this);
        $user->xp_vb_is_verified = 1;
        $user->save();

        return;
    }

    protected function removeUserBadge()
    {
        if (!$this->User)
        {
            return;
        }

        $user = $this->User;
        $user->xp_vb_is_verified = 0;
        $user->save();

        return;
    }

	protected function _postDelete()
	{
		// TODO maybe build in some hard delete functions so that request records (and their data) can be permanently removed)
	}

	public static function getStructure(Structure $structure)
	{
		$structure->table = 'xf_xp_vb_request';
		$structure->shortName = 'XP\VB:Request';
		$structure->primaryKey = 'request_id';
		$structure->contentType = 'vb_request';
		
		$structure->columns = [
			'request_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],
			'user_id' => ['type' => self::UINT, 'required' => true],
			'username' => ['type' => self::STR, 'maxLength' => 50,
				'required' => 'please_enter_valid_name'
			],			
			'request_date' => ['type' => self::UINT, 'default' => \XF::$time],
			'edit_date' => ['type' => self::UINT, 'default' => \XF::$time],
			'approve_date' => ['type' => self::UINT, 'default' => 0],
			'reject_date' => ['type' => self::UINT, 'default' => 0],
			'close_date' => ['type' => self::UINT, 'default' => 0],
			'request_status' => ['type' => self::STR, 'default' => 'pending',
				'allowedValues' => ['pending', 'approved', 'rejected', 'closed']
			],
			'message' => ['type' => self::STR, 'default' => ''],
			'status_message' => ['type' => self::STR, 'default' => ''],			
			'attach_count' => ['type' => self::UINT, 'default' => 0],
			'ip_id' => ['type' => self::UINT, 'default' => 0],
			'embed_metadata' => ['type' => self::JSON_ARRAY, 'nullable' => true, 'default' => null]
		];
		$structure->getters = [];
		$structure->behaviors = [];
		$structure->relations = [
			'User' => [
				'entity' => 'XF:User',
				'type' => self::TO_ONE,
				'conditions' => 'user_id',
				'primary' => true
			],
			'Attachments' => [
				'entity' => 'XF:Attachment',
				'type' => self::TO_MANY,
				'conditions' => [
					['content_type', '=', 'vb_request'],
					['content_id', '=', '$request_id']
				],
				'with' => 'Data',
				'order' => 'attach_date'
			],
			'DeletionLog' => [
				'entity' => 'XF:DeletionLog',
				'type' => self::TO_ONE,
				'conditions' => [
					['content_type', '=', 'vb_request'],
					['content_id', '=', '$request_id']
				],
				'primary' => true
			]

		];
		$structure->defaultWith = [
			'User'
		];
		$structure->options = [
			'log_moderator' => true
		];

		return $structure;
	}
}