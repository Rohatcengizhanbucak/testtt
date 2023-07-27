<?php

namespace XP\VB\ControllerPlugin;

use XF\ControllerPlugin\AbstractPlugin;

class RequestList extends AbstractPlugin
{
	public function getRequestListData()
	{
		$requestRepo = $this->getRequestRepo();
		$options = $this->options();

		$requestFinder = $requestRepo->findRequestsForRequestList();

		$filters = $this->getRequestFilterInput();
		$this->applyRequestFilters($requestFinder, $filters);
		
		$page = $this->filterPage();
		$perPage = $options->xpVbRequestsPerPage;
		
		$requestFinder->limitByPage($page, $perPage);
		
		$requests = $requestFinder->fetch()->filterViewable();
		$totalRequests = $requestFinder->total();
		
		/** @var \XF\Repository\Attachment $attachRepo */
		$attachRepo = $this->repository('XF:Attachment');
		$attachRepo->addAttachmentsToContent($requests, 'vb_request');

		if (!empty($filters['creator_id']))
		{
			$creatorFilter = $this->em()->find('XF:User', $filters['creator_id']);
		}
		else
		{
			$creatorFilter = null;
		}
		
		return [
			'requests' => $requests,
			'filters' => $filters,
			'creatorFilter' => $creatorFilter,

			'total' => $totalRequests,
			'page' => $page,
			'perPage' => $perPage
		];
	}

	public function applyRequestFilters(\XP\VB\Finder\Request $requestFinder, array $filters)
	{
		if (!empty($filters['request_status']))
		{
			switch ($filters['request_status'])
			{
				case 'pending':
					$requestFinder->where('request_status', 'pending');
					break;
		
				case 'approved':
					$requestFinder->where('request_status', 'approved');
					break;
		
				case 'rejected':
					$requestFinder->where('request_status', 'rejected');
					break;
                case 'closed':
                    $requestFinder->where('request_status', 'closed');
                    break;
			}
		}

		if (!empty($filters['creator_id']))
		{
			$requestFinder->where('user_id', intval($filters['creator_id']));
		}

		$sorts = $this->getAvailableRequestSorts();

		if (!empty($filters['order']) && isset($sorts[$filters['order']]))
		{
			$requestFinder->order($sorts[$filters['order']], $filters['direction']);
		}
		// else the default order has already been applied
	}

	public function getRequestFilterInput()
	{
		$filters = [];

		$input = $this->filter([
			'request_status' => 'str',
			'creator' => 'str',
			'creator_id' => 'uint',
			'order' => 'str',
			'direction' => 'str'
		]);
		
		if ($input['request_status'] && ($input['request_status'] == 'pending' || $input['request_status'] == 'approved' || $input['request_status'] == 'rejected' || $input['request_status'] == 'closed'))
		{
			$filters['request_status'] = $input['request_status'];
		}

		if ($input['creator_id'])
		{
			$filters['creator_id'] = $input['creator_id'];
		}
		else if ($input['creator'])
		{
			$user = $this->em()->findOne('XF:User', ['username' => $input['creator']]);
			if ($user)
			{
				$filters['creator_id'] = $user->user_id;
			}
		}

		$sorts = $this->getAvailableRequestSorts();

		if ($input['order'] && isset($sorts[$input['order']]))
		{
			if (!in_array($input['direction'], ['asc', 'desc']))
			{
				$input['direction'] = 'desc';
			}

			$defaultOrder = 'request_date';
			$defaultDir = 'desc';

			if ($input['order'] != $defaultOrder || $input['direction'] != $defaultDir)
			{
				$filters['order'] = $input['order'];
				$filters['direction'] = $input['direction'];
			}
		}

		return $filters;
	}

	public function getAvailableRequestSorts()
	{
		return [
			'request_date' => 'request_date'
		];
	}

	public function actionFilters()
	{
		$filters = $this->getRequestFilterInput();

		if ($this->filter('apply', 'bool'))
		{
			return $this->redirect($this->buildLink('vb/requests', null, $filters));
		}

		if (!empty($filters['creator_id']))
		{
			$creatorFilter = $this->em()->find('XF:User', $filters['creator_id']);
		}
		else
		{
			$creatorFilter = null;
		}
		
		$defaultOrder = 'request_date';
		$defaultDir = 'desc';

		if (empty($filters['order']))
		{
			$filters['order'] = $defaultOrder;
		}
		if (empty($filters['direction']))
		{
			$filters['direction'] = $defaultDir;
		}

		$viewParams = [
			'filters' => $filters,
			'creatorFilter' => $creatorFilter,
		];
		return $this->view('XP\VB:Filters', 'xp_vb_request_list_filters', $viewParams);
	}

	/**
	 * @return \XP\VB\Repository\Request
	 */
	protected function getRequestRepo()
	{
		return $this->repository('XP\VB:Request');
	}
}