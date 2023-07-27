<?php

namespace XP\VB\Finder;

use XF\Mvc\Entity\Finder;

class Request extends Finder
{
	public function byUser(\XF\Entity\User $user)
	{
		$this->where('user_id', $user->user_id);
	
		return $this;
	}

	public function forFullView()
	{
		$visitor = \XF::visitor();

		$this->with(['User']);

		return $this;
	}
	
	/**
	 * @param string $direction
	 *
	 * @return Finder
	 */
	public function orderByDate($order = 'request_date', $direction = 'DESC')
	{
		$this->setDefaultOrder([
			[$order, $direction],
			['request_id', $direction]
		]);
	
		return $this;
	}	

	public function useDefaultOrder()
	{
		$defaultOrder = 'request_date';
		$defaultDir = 'desc';

		$this->setDefaultOrder($defaultOrder, $defaultDir);

		return $this;
	}

}