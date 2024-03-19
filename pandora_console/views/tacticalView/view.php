<?php global $config; ?>
<div id="general-tactical-view">
    <div id="welcome-message">
        <?php echo $welcome; ?>
        <span class="subtitle-welcome-message"><?php echo __('This is the latest data in your tactical view'); ?></span>
    </div>
    <div class="row">
        <div class="col-xl-6">
            <div id="general-overview" class="pdd_5px">
                <div class="container">
                    <div class="title">
                        <?php echo $Overview->title; ?>
                    </div>
                    <div class="content br-t">
                        <div class="row">
                            <div class="col-12">
                                <div class="row">
                                    <div class="col-4">
                                        <div class="padding10">
                                            <span class="subtitle">
                                                <?php echo __('Pandora FMS log size'); ?>
                                            </span>
                                            <?php echo $Overview->getLogSizeStatus(); ?>
                                        </div>
                                    </div>
                                    <div class="col-4 br-l">
                                        <div class="padding10">
                                            <span class="subtitle">
                                                <?php echo __('Server status'); ?>
                                            </span>
                                            <?php echo $Overview->getServerStatus(); ?>
                                        </div>
                                    </div>
                                    <div class="col-4 br-l">
                                        <div class="padding10">
                                            <span class="subtitle">
                                                <?php echo __('System CPU Load'); ?>
                                            </span>
                                            <?php echo $Overview->getCPULoadGraph(); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($disableGeneralStatistics === false) : ?>
                                    <div class="br-t">
                                        <div class="padding10">
                                            <span class="subtitle">
                                                <?php echo __('License usage'); ?>
                                            </span>
                                            <?php echo $Overview->getLicenseUsageGraph(); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row flex-nowrap height_100p">
                <div class="col-7 pdd_5px">
                    <div class="container">
                        <div class="title">
                            <?php echo $MonitoringElements->title; ?>
                        </div>
                        <div class="content br-t height_100p">
                            <div class="row height_50p">
                                <div class="col-6 br-r br-b">
                                    <div class="subtitle link padding10 padding2">
                                        <?php echo __('Status (%)'); ?> <a href="index.php?sec=view&sec2=operation/agentes/estado_agente"><?php echo __('Info'); ?></a>
                                    </div>
                                    <?php echo $MonitoringElements->getMonitoringStatusGraph(); ?>
                                </div>
                                <div class="col-6 br-b">
                                    <div class="subtitle link padding10 padding2">
                                        <?php echo __('Top-10 module groups'); ?> <a href="index.php?sec=view&sec2=extensions/module_groups"><?php echo __('Info'); ?></a>
                                    </div>
                                    <?php echo $MonitoringElements->getModuleGroupGraph(); ?>
                                </div>
                            </div>
                            <div class="row height_50p">
                                <div class="col-6">
                                    <div class="subtitle link padding10 padding2">
                                        <?php echo __('Top-10 Tags'); ?> <a href="index.php?sec=gusuarios&sec2=godmode/tag/tag"><?php echo __('Info'); ?></a>
                                    </div>
                                    <?php echo $MonitoringElements->getTagsGraph(); ?>
                                </div>
                                <div class="col-6 br-l">
                                    <div class="subtitle link padding10 padding2">
                                        <?php echo __('Top-10 Groups'); ?> <a href="index.php?sec=view&sec2=operation/agentes/group_view"><?php echo __('Info'); ?></a>
                                    </div>
                                    <?php echo $MonitoringElements->getAgentGroupsGraph(); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="col-5 pdd_5px">
                    <div class="container" id="database">
                        <div class="title">
                            <?php echo $Database->title; ?>
                        </div>
                        <div class="content br-t">
                            <div class="row">
                                <div class="col-6 br-r br-b">
                                    <div class="subtitle">
                                        <?php echo __('Database status'); ?>
                                    </div>
                                    <?php echo $Database->getStatus(); ?>
                                </div>
                                <div class="col-6 br-b">
                                    <div class="subtitle">
                                        <?php echo __('Data records'); ?>
                                    </div>
                                    <?php echo $Database->getDataRecords(); ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="subtitle">
                                        <?php echo __('String records'); ?>
                                    </div>
                                    <?php echo $Database->getStringRecords(); ?>
                                </div>
                                <div class="col-6 br-l">
                                    <div class="subtitle">
                                        <?php echo __('Events'); ?>
                                    </div>
                                    <?php echo $Database->getEvents(); ?>
                                </div>
                            </div>
                            <div class="br-t">
                                <div class="subtitle padding10 padding2">
                                    <?php echo __('Reads (last 24 hrs)'); ?>
                                </div>
                                <?php echo $Database->getReadsGraph(); ?>
                            </div>
                            <div class="br-t">
                                <div class="subtitle padding10 padding2">
                                    <?php echo __('Writes (last 24 hrs)'); ?>
                                </div>
                                <?php echo $Database->getWritesGraph(); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="container mrgn_5px">
                <div class="title">
                    <?php echo $NewsBoard->title; ?>
                </div>
                <?php echo $NewsBoard->getNews(); ?>
            </div>
            <div class="row">
                <div class="col-6 pdd_5px">
                    <div class="container">
                        <div class="title br-b" id="heatmap-title">
                            <?php echo $Groups->title; ?>
                        </div>
                        <div class="subtitle link padding10 padding2">
                            <?php echo __('Status'); ?> <a href="index.php?sec=view&sec2=operation/agentes/group_view"><?php echo __('Info'); ?></a>
                        </div>
                        <div id="heatmap-group">
                            <?php echo $Groups->loading(); ?>
                        </div>
                    </div>
                </div>
                <?php if ($disableGeneralStatistics === false) : ?>
                <div class="col-6">
                    <div class="container mrgn_5px" id="logStorage">
                        <div class="title br-b">
                            <?php echo $LogStorage->title; ?>
                        </div>
                        <div class="row">
                            <div class="col-6 br-r br-b">
                                <div class="subtitle">
                                    <?php echo __('Log storage status'); ?>
                                </div>
                                <?php echo $LogStorage->getStatus(); ?>
                            </div>
                            <div class="col-6 br-b">
                                <div class="subtitle">
                                    <?php echo __('Total sources'); ?>
                                </div>
                                <?php echo $LogStorage->getTotalSources(); ?>
                            </div>
                        </div>
                        <div class="row height_100p">
                            <div class="col-6 br-r">
                                <div class="subtitle">
                                    <?php echo __('Stored data'); ?>
                                </div>
                                <?php echo $LogStorage->getStoredData(); ?>
                                <span class="indicative-text"><?php echo __('Documents'); ?></span>
                            </div>
                            <div class="col-6">
                                <div class="subtitle">
                                    <?php echo __('Age of stored data'); ?>
                                </div>
                                <?php echo $LogStorage->getAgeOfStoredData(); ?>
                                <span class="indicative-text"><?php echo __('Days'); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="container mrgn_5px" id="SNMPTraps">
                        <div class="title br-b">
                            <?php echo $SnmpTraps->title; ?>
                        </div>
                        <div class="row">
                            <div class="col-6 br-r">
                                <div class="subtitle">
                                    <?php echo __('Trap queues'); ?>
                                </div>
                                <?php echo $SnmpTraps->getQueues(); ?>
                            </div>
                            <div class="col-6">
                                <div class="subtitle">
                                    <?php echo __('Total sources'); ?>
                                </div>
                                <?php echo $SnmpTraps->getTotalSources(); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="<?php echo (($Events->checkAcl() === true)) ? 'col-md-3 pdd_5px' : 'col-12 pdd_5px'; ?>">
            <div class="container" id="Alerts">
                <div class="title br-b">
                    <?php echo $Alerts->title; ?>
                </div>
                <div class="row br-b">
                    <div class="col-6">
                        <div class="subtitle">
                            <?php echo __('Currently triggered'); ?>
                        </div>
                        <a href="index.php?sec=galertas&sec2=godmode/alerts/alert_list&status_alert=fired"><?php echo $Alerts->getCurrentlyTriggered(); ?></a>
                    </div>
                    <div class="col-6 br-l">
                        <div class="subtitle">
                            <?php echo __('Active alerts'); ?>
                        </div>
                        <a href="index.php?sec=galertas&sec2=godmode/alerts/alert_list&status_alert=all_enabled"><?php echo $Alerts->getActiveAlerts(); ?></a>
                    </div>
                </div>
                <?php if ($Alerts->checkAclUserList() === true) : ?>
                    <div id="list-users">
                        <div class="subtitle link padding10 padding2">
                            <b><?php echo __('Logged in users (24 hrs)'); ?></b> <a href="index.php?sec=gusuarios&sec2=godmode/users/user_list"><?php echo __('More details'); ?></a>
                        </div>
                        <?php echo $Alerts->getDataTableUsers(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($Events->checkAcl() === true) : ?>
            <div class="col-md-9 pdd_5px">
                <div class="container overflow_hidden" id="Events">
                    <div class="title br-b">
                        <?php echo $Events->title; ?>
                    </div>
                    <div class="row" id="auto-rescaling">
                        <div class="col-8 br-r trigger-100">
                            <div class="subtitle link padding10 padding2">
                                <?php echo __('Number of events per hour (').$config['event_view_hr'].' hrs)'; ?></b> <a href="index.php?sec=eventos&sec2=operation/events/events&filter[event_view_hr]=<?php echo $config['event_view_hr']; ?>&filter[tag_with]=WyIwIl0=&filter[tag_without]=WyIwIl0="><?php echo __('Info'); ?></a>
                            </div>
                            <div id="events-last-24"><?php echo $Events->loading(); ?></div>
                            <div class="row br-t h100p observer">
                                <div class="col-4 br-r">
                                    <div class="subtitle padding10 padding2">
                                        <?php echo __('Criticality'); ?></b>
                                    </div>
                                    <div id="events-criticality"><?php echo $Events->loading(); ?></div>
                                </div>
                                <div class="col-4 br-r">
                                    <div class="subtitle padding10 padding2">
                                        <?php echo __('Status (%)'); ?></b>
                                    </div>
                                    <div id="events-status-validate"><?php echo $Events->loading(); ?></div>
                                </div>
                                <div class="col-4">
                                    <div class="subtitle padding10 padding2">
                                        <?php echo __('Pending validation'); ?></b>
                                    </div>
                                    <div id="events-status-pending-validate"><?php echo $Events->loading(); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4 trigger-100">
                            <div class="subtitle link padding10 padding2">
                                <?php echo __('Active events (').$config['event_view_hr'].' hrs)'; ?></b> <a href="index.php?sec=eventos&sec2=operation/events/events"><?php echo __('Info'); ?></a>
                            </div>
                            <?php echo $Events->getDataTableEvents(); ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="row">
        <div class="col-xl-6 pdd_5px">
            <div class="container" id="Agents">
                <div class="title br-b">
                    <?php echo $Agents->title; ?>
                </div>
                <div class="row">
                    <div class="col-6 br-r">
                        <div class="row br-b">
                            <div class="col-6 br-r">
                                <div class="subtitle">
                                    <?php echo __('Total agents'); ?>
                                </div>
                                <?php echo $Agents->getTotalAgents(); ?>
                            </div>
                            <div class="col-6">
                                <div class="subtitle">
                                    <?php echo __('Alerts (24hrs)'); ?>
                                </div>
                                <?php echo $Agents->getAlerts(); ?>
                            </div>
                        </div>
                        <div class="subtitle link padding10 padding2">
                            <?php echo __('Top 20 groups'); ?></b> <a href="index.php?sec=view&sec2=operation/agentes/estado_agente"><?php echo __('More details'); ?></a>
                        </div>
                        <?php echo $Agents->getDataTableGroups(); ?>
                    </div>
                    <div class="col-6">
                        <div class="subtitle padding10 padding2">
                            <?php echo __('Operating system'); ?></b>
                        </div>
                        <?php echo $Agents->getOperatingSystemGraph(); ?>
                        <div class="subtitle padding10 padding2 br-t">
                            <?php echo __('Status (%)'); ?></b>
                        </div>
                        <?php echo $Agents->getStatusGraph(); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <?php if ($disableGeneralStatistics === false) : ?>
                <div class="container mrgn_5px">
                    <div class="title br-b">
                        <?php echo $Configurations->title; ?>
                    </div>
                    <div class="row br-b flex-nowrap">
                        <a href="index.php?sec=view&sec2=operation/agentes/group_view" class="col-3 flex flex-column center pdd_20px br-r">
                            <?php echo $Configurations->getTotalGroups(); ?>
                        </a>
                        <a href="index.php?sec=view&sec2=extensions/agents_modules" class="col-3 flex flex-column center pdd_20px br-r">
                            <?php echo $Configurations->getTotalModules(); ?>
                        </a>
                        <?php if (enterprise_installed() === true) : ?>
                            <a href="index.php?sec=gmodules&sec2=enterprise/godmode/policies/policies" class="col-3 flex flex-column center pdd_20px br-r">
                                <?php echo $Configurations->getTotalPolicies(); ?>
                            </a>
                        <?php endif; ?>
                        <a href="index.php?sec=gservers&sec2=godmode/servers/plugin" class="col-3 flex flex-column center pdd_20px">
                            <?php echo $Configurations->getTotalRemotePlugins(); ?>
                        </a>
                    </div>
                    <div class="row flex-nowrap br-b">
                        <a href="index.php?sec=templates&sec2=godmode/modules/manage_module_templates" class="col-4 flex flex-column center pdd_20px br-r">
                            <?php echo $Configurations->getTotalModuleTemplate(); ?>
                        </a>
                        <a href="index.php?sec=view&sec2=operation/agentes/estado_agente&status=5" class="col-4 flex flex-column center pdd_20px br-r">
                            <?php echo $Configurations->getNotInitModules(); ?>
                        </a>
                        <a href="index.php?sec=view&sec2=operation/agentes/estado_agente&status=3" class="col-4 flex flex-column center pdd_20px br-r">
                            <?php echo $Configurations->getTotalUnknowAgents(); ?>
                        </a>
                        <a href="index.php?sec=eventos&sec2=operation/events/events" class="col-4 flex flex-column center pdd_20px">
                            <?php echo $Configurations->getTotalEvents(); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($ScheduledDowntime->checkAcl() === true) : ?>
                <div class="container mrgn_5px">
                    <div class="title br-b">
                        <?php echo $ScheduledDowntime->title; ?>
                    </div>
                    <?php echo $ScheduledDowntime->list(); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
echo $javascript;
