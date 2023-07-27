<?php

namespace XP\VB;

class Listener
{
    protected static $_productId = 30;
	public static function appSetup(\XF\App $app)
	{
		$container = $app->container();

        /*XP_BRANDING_START*/
        if (!$app->offsetExists('xp_branding_free'))
        {
            // Make sure we fetch the branding array from the application
            $branding = $app->offsetExists('xp_branding') ? $app->xp_branding : [];

            // Add productid to the array
            $branding[] = self::$_productId;

            // Store the branding
            $app->xp_branding = $branding;
        }
        /*XP_BRANDING_END*/
	}
	
	public static function criteriaUser($rule, array $data, \XF\Entity\User $user, &$returnValue)
	{
		switch ($rule)
		{
            case 'xp_vb_is_verified':
                if (isset($user->xp_vb_is_verified) && $user->xp_vb_is_verified == 1)
                {
                    $returnValue = true;
                }
                break;
            case 'xp_vb_is_verified_no':
                if (!isset($user->xp_vb_is_verified) || $user->xp_vb_is_verified == 0)
                {
                    $returnValue = true;
                }
                break;
            case 'xp_vb_verificationrequest_count':
                if (isset($user->xp_vb_verificationrequest_count) && $user->xp_vb_verificationrequest_count >= $data['vb_request'])
                {
                    $returnValue = true;
                }
                break;
            case 'xp_vb_verificationrequest_count_nmt':
                if (isset($user->xp_vb_verificationrequest_count) && $user->xp_vb_verificationrequest_count <= $data['vb_request'])
                {
                    $returnValue = true;
                }
                break;
        }
	}
	
	/**
	 * @return \XP\VB\XF\Entity\User
	 */
	public static function visitor()
	{
		/** @var \XP\VB\XF\Entity\User $visitor */
		$visitor = \XF::visitor();
		return $visitor;
	}	
}