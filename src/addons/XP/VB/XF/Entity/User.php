<?php

namespace XP\VB\XF\Entity;

use XP\VB\Entity\Request;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int user_id
 * @property string username
 * @property int ip_id
 *
 * RELATIONS
 * @property \XF\Entity\Attachment[] Attachments
 * @property \XF\Entity\User User
 * @property \XP\VB\Entity\Request[] Request
 *
 * */

class User extends XFCP_User
{
    public function hasVbRequestPermission($permission)
    {
        return $this->hasPermission('xp_vb', $permission);

    }

    public function canRequestVb(&$error = null)
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id)
        {
            return false;
        }

        if ($this->hasPermission('xp_vb','request')){
            $maxRequestCount = $this->hasPermission('xp_vb','maxRequestCount');
            $userRequestCount = \XF::visitor()->xp_vb_verificationrequest_count;

            if ($maxRequestCount == -1 || $maxRequestCount == 0) // unlimited NOTE: in this particular case, we want 0 to count as unlimited.
            {
                return true;
            }

            if ($userRequestCount < $maxRequestCount)
            {
                return true;
            }

            return false;
        }
        return $this->hasPermission('xp_vb','request');
    }

    public function getNewVbRequest()
    {
        $request = $this->_em->create('XP\VB:Request');
        $request->user_id = $this->user_id;

        return $request;
    }

    public function userRequestedVb(Request $request)
    {
        $this->request_date = $request->approve_date;
        $this->request_id = $request->request_id;
        $this->user_id = $request->user_id;
        $this->username = $request->username;

    }

    public static function getStructure(Structure $structure)
	{
		$structure = parent::getStructure($structure);

		$structure->columns['xp_vb_is_verified'] = ['type' => self::BOOL, 'default' => 0];
        $structure->columns['xp_vb_verificationrequest_count'] = ['type' => self::UINT, 'default' => 0, 'forced' => true, 'changeLog' => false];
		return $structure;
	}
}