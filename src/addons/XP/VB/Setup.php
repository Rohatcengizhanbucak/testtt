<?php

namespace XP\VB;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    // ################################ INSTALLATION ####################

	public function installStep1()
	{
        $sm = $this->schemaManager();

        $sm->alterTable('xf_user', function(Alter $table)
        {
            $table->addColumn('xp_vb_is_verified', 'tinyint', 1)->setDefault(0);
            $table->addColumn('xp_vb_verificationrequest_count', 'int')->setDefault(0);
            $table->addKey('xp_vb_verificationrequest_count');
        });
	}

    public function installStep2()
    {
        $sm = $this->schemaManager();

        $sm->createTable('xf_xp_vb_request', function(\XF\Db\Schema\Create $table)
        {
            $table->addColumn('request_id', 'int')->autoIncrement();
            $table->addColumn('user_id', 'int');
            $table->addColumn('username', 'varchar', 50)->setDefault('');
            $table->addColumn('request_date', 'int')->setDefault(0);
            $table->addColumn('edit_date', 'int')->setDefault(0);
            $table->addColumn('approve_date', 'int')->setDefault(0);
            $table->addColumn('reject_date', 'int')->setDefault(0);
            $table->addColumn('close_date', 'int')->setDefault(0);
            $table->addColumn('request_status', 'enum')->values(['pending','approved','rejected', 'closed'])->setDefault('pending');
            $table->addColumn('message', 'mediumtext');
            $table->addColumn('status_message', 'mediumtext');
            $table->addColumn('attach_count', 'int')->setDefault(0);
            $table->addColumn('ip_id', 'int')->setDefault(0);
            $table->addColumn('embed_metadata', 'blob')->nullable();
            $table->addKey(['request_id', 'request_date'], 'request_id_request_date');
            $table->addKey('user_id');
            $table->addKey('request_date');
            $table->addKey('request_status');
        });
    }

    // ############################################ UNINSTALL #########################

	public function uninstallStep1()
	{
        $sm = $this->schemaManager();

        $sm->alterTable('xf_user', function(Alter $table)
        {
            $table->dropColumns('xp_vb_is_verified');
            $table->dropColumns('xp_vb_verificationrequest_count');
        });
    }

    public function uninstallStep2()
    {
        $sm = $this->schemaManager();

        $sm->dropTable('xf_xp_vb_request');

        $contentTypes = ['vb_request'];

        $this->uninstallContentTypeData($contentTypes);

    }
}