<?php

namespace XP\VB\XF\Admin\Controller;

class User extends XFCP_User
{
    protected function userSaveProcess(\XF\Entity\User $user)
    {
        $form = parent::userSaveProcess($user);

        $form->setup(function() use($user)
        {
            $user->xp_vb_is_verified = $this->filter('xp_vb_is_verified', 'bool');
        });

        return $form;
    }
}