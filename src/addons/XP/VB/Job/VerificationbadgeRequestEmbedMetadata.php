<?php

namespace XP\VB\Job;

use XF\Job\AbstractEmbedMetadataJob;
use XF\Mvc\Entity\Entity;

class VbRequestEmbedMetadata extends AbstractEmbedMetadataJob
{
	protected function getIdsToRebuild(array $types)
	{
		$db = $this->app->db();

		// Note: only attachments are supported currently, so we filter based on attach count for efficiency.
		// If other types become available, this condition will need to change.
		return $db->fetchAllColumn($db->limit(
			"
				SELECT request_id
				FROM xf_xp_vb_request
				WHERE request_id > ?
					AND attach_count > 0
				ORDER BY request_id
			", $this->data['batch']
		), $this->data['start']);
	}

	protected function getRecordToRebuild($id)
	{
		return $this->app->em()->find('XP\VB:Request', $id);
	}

	protected function getPreparerContext()
	{
		return 'vb_request';
	}

	protected function getMessageContent(Entity $record)
	{
		return $record->message;
	}

	protected function rebuildAttachments(Entity $record, \XF\Service\Message\Preparer $preparer, array &$embedMetadata)
	{
		$embedMetadata['attachments'] = $preparer->getEmbeddedAttachments();
	}

	protected function getActionDescription()
	{
		$rebuildPhrase = \XF::phrase('rebuilding');
		$type = \XF::phrase('xp_vb_requests');
		return sprintf('%s... %s', $rebuildPhrase, $type);
	}
}