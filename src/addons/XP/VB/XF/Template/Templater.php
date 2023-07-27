<?php

namespace XP\VB\XF\Template;

/**
 * COLUMNS
 * @property bool xp_vb_is_verified
 */

class Templater extends XFCP_Templater
{
    public function fnUsernameLink($templater, &$escape, $user, $rich = false, $attributes = [])
    {
        $username = parent::fnUsernameLink($templater, $escape, $user, $rich, $attributes);
      $visitor = \XF::visitor();

        if (!$user)
        {
            return $username;
        }

        if (!is_object($user))
        {
            if (!isset($user['user_id']))
            {
                return $username;
            }

            $user = \XF::em()->instantiateEntity('XF:User', $user);
        }

        if ($user instanceof \XF\Entity\User)
        {
            $hasVB   = 0;
            $vbType = \XF::options()->xpVbType;
          
          	if ($visitor->hasPermission('view', 'XP_VB_Badge'))
            {              
              if ($vbType == 'default'){
                  $vB = '<i data-xf-init="tooltip" data-original-title="' . \XF::phrase('xp_vb_tooltip') . '" class="fa--xf far fa-check-circle fa-lg fa-fw xpvb" aria-hidden="true"></i>';
              } else if($vbType == 'icon'){
                  $vB = '<i data-xf-init="tooltip" data-original-title="' . \XF::phrase('xp_vb_tooltip') . '" class="fa--xf far '.\XF::options()->xpCustomVbIcon.' fa-lg fa-fw xpvb" aria-hidden="true"></i>';
              } else if($vbType == 'image'){
                  $vB = '<img data-xf-init="tooltip" data-original-title="' . \XF::phrase('xp_vb_tooltip') . '" src="' . \XF::options()->xpCustomVbImage . '" style="width: 20px; margin-bottom: -5px;" class="xpvb" aria-hidden="true" />';
              }
            }

            if ($user['xp_vb_is_verified']){
                $hasVB = true;
            }

            if ($hasVB)
            {
                if (\XF::options()->xpVB_location  == 'left')
                {
                    $username = $vB . $username;
                }
                else
                {
                    $username = $username . $vB;
                }

            }
        }

        return $username;
    }
}