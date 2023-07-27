<?php

namespace XP\VB\Pub\Controller;

use XF\Pub\Controller\AbstractController;

class Verified extends AbstractController
{
    //public function actionIndex(ParameterBag $params)
  	public function actionIndex()
    {
      	// Get variables
      	$visitor = \XF::visitor();
      	$options = \XF::options();
      
      	// Show error if no permission
		if (!$visitor->hasPermission('view', 'XP_VB_List'))
		{
			return $this->noPermission();
		}
      
        $finder = \XF::finder('XF:User')
          	->where('xp_vb_is_verified', '=',1);

        $total = $finder->total();
        $page = $this->filterPage();
      	$perPage = $options->XPVBmembersPerPage;

        $this->assertValidPage($page, $perPage, $total, 'verified', $finder);

        $verified = $finder->limitByPage($page, $perPage)->order('user_id', 'ASC')->fetch();

        $viewParams = [
            'verified' => $verified,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total
        ];

      	// Okay send it to the template
      	return $this->view('XP\VB:Verified', 'xp_vb_list', $viewParams);
    }
  
  	// Show the location when viewing who's oline
	public static function getActivityDetails(array $activities)
	{
		return \XF::phrase('XP_VB_verified_viewing_list');
	}
}