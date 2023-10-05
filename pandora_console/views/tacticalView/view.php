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
                        <div class="col-6">
                            <div class="row">
                                <div class="<?php echo ($Overview->wuxIsEnabled() === true) ? 'col-6' : 'col-12'; ?>">
                                    <div class="padding10 <?php echo ($Overview->wuxIsEnabled() === true) ? '' : 'center'; ?>">
                                        <span class="subtitle">
                                            <?php echo __('Pandora FMS log size'); ?>
                                        </span>
                                        <?php echo $Overview->getLogSizeStatus(); ?>
                                    </div>
                                </div>
                                <?php if ($Overview->wuxIsEnabled() === true) : ?>
                                    <div class="col-6 br-l">
                                        <div class="padding10">
                                            <span class="subtitle">
                                                <?php echo __('Wux server status'); ?>
                                            </span>
                                            <?php echo $Overview->getWuxServerStatus(); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="br-t">
                                <div class="padding10">
                                    <span class="subtitle link">
                                        <?php echo __('License usage'); ?> <a href=""><?php echo __('Info'); ?></a>
                                    </span>
                                    <?php echo $Overview->getLicenseUsageGraph(); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 br-l relative">
                            <div class="subtitle link padding10 padding2">
                                <?php echo __('XML packets processed (last 24 hrs)'); ?> <a href=""><?php echo __('Info'); ?></a>
                            </div>
                            <?php echo $Overview->getXmlProcessedGraph(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-7 pdd_5px">
                <div class="container">
                    <div class="title">
                        <?php echo $MonitoringElements->title; ?>
                    </div>
                    <div class="content br-t">
                        <div class="row">
                            <div class="col-6 br-r br-b">
                                <div class="subtitle link padding10 padding2">
                                    <?php echo __('Top-10 Tags'); ?> <a href=""><?php echo __('Info'); ?></a>
                                </div>
                                <?php echo $MonitoringElements->getTagsGraph(); ?>
                            </div>
                            <div class="col-6 br-b">
                                <div class="subtitle link padding10 padding2">
                                    <?php echo __('Top-10 module groups'); ?> <a href=""><?php echo __('Info'); ?></a>
                                </div>
                                <?php echo $MonitoringElements->getModuleGroupGraph(); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="subtitle link padding10 padding2">
                                    <?php echo __('Status'); ?> <a href=""><?php echo __('Info'); ?></a>
                                </div>
                                <?php echo $MonitoringElements->getMonitoringStatusGraph(); ?>
                            </div>
                            <div class="col-6 br-l">
                                <div class="subtitle link padding10 padding2">
                                    <?php echo __('Top-10 Groups'); ?> <a href=""><?php echo __('Info'); ?></a>
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
                            <div class="subtitle link padding10 padding2">
                                <?php echo __('Reads (last 24 hrs)'); ?> <a href=""><?php echo __('Info'); ?></a>
                            </div>
                            <?php echo $Database->getReadsGraph(); ?>
                        </div>
                        <div class="br-t">
                            <div class="subtitle link padding10 padding2">
                                <?php echo __('Writes (last 24 hrs)'); ?> <a href=""><?php echo __('Info'); ?></a>
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
            <?php if ($Groups->total < 200) : ?>
                <div class="<?php echo (($SnmpTraps->isEnabled() === true && $LogStorage->isEnabled() === true)) ? 'col-6 pdd_5px' : 'col-12 pdd_5px'; ?>">
                    <div class="container">
                        <div class="title br-b">
                            <?php echo $Groups->title; ?>
                        </div>
                        <div class="subtitle link padding10 padding2">
                            <?php echo __('Status'); ?> <a href=""><?php echo __('Info'); ?></a>
                        </div>
                        <div id="heatmap-group">
                            <?php echo $Groups->loading(); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($LogStorage->isEnabled() === true && $SnmpTraps->isEnabled() === true) : ?>
                <div class="col-6">
                    <?php if ($LogStorage->isEnabled() === true) : ?>
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
                            <div class="row">
                                <div class="col-6 br-r">
                                    <div class="subtitle">
                                        <?php echo __('Stored data'); ?>
                                    </div>
                                    <?php echo $LogStorage->getStoredData(); ?>
                                    <span class="indicative-text"><?php echo __('Lines'); ?></span>
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
                    <?php endif; ?>
                    <?php if ($SnmpTraps->isEnabled() === true) : ?>
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
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3 pdd_5px">
        <div class="container" id="Alerts">
            <div class="title br-b">
                <?php echo $Alerts->title; ?>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="subtitle">
                        <?php echo __('Currently triggered'); ?>
                    </div>
                    <?php echo $Alerts->getCurrentlyTriggered(); ?>
                </div>
                <div class="col-6 br-l">
                    <div class="subtitle">
                        <?php echo __('Active correlation'); ?>
                    </div>
                    <?php echo $Alerts->getActiveCorrelation(); ?>
                </div>
            </div>
            <div id="list-users">
                <div class="subtitle link padding10 padding2 br-t">
                    <b><?php echo __('Logged in users (24 hrs)'); ?></b> <a href=""><?php echo __('More details'); ?></a>
                </div>
                <?php echo $Alerts->getDataTableUsers(); ?>
            </div>
        </div>
    </div>
    <div class="col-md-9 pdd_5px">
        <div class="container overflow_hidden" id="Events">
            <div class="title br-b">
                <?php echo $Events->title; ?>
            </div>
            <div class="row">
                <div class="col-8 br-r">
                    <div class="subtitle link padding10 padding2">
                        <?php echo __('Number of events per hour (24 hrs)'); ?></b> <a href=""><?php echo __('Info'); ?></a>
                    </div>
                    <div id="events-last-24"><?php echo $Events->loading(); ?></div>
                    <div class="row br-t h100p">
                        <div class="col-4 br-r">
                            <div class="subtitle link padding10 padding2">
                                <?php echo __('Criticality'); ?></b> <a href=""><?php echo __('Info'); ?></a>
                            </div>
                            <div id="events-criticality"><?php echo $Events->loading(); ?></div>
                        </div>
                        <div class="col-4 br-r">
                            <div class="subtitle link padding10 padding2">
                                <?php echo __('Status'); ?></b> <a href=""><?php echo __('Info'); ?></a>
                            </div>
                            <div id="events-status-validate"><?php echo $Events->loading(); ?></div>
                        </div>
                        <div class="col-4">
                            <div class="subtitle link padding10 padding2">
                                <?php echo __('Pending validation'); ?></b> <a href=""><?php echo __('Info'); ?></a>
                            </div>
                            <div id="events-status-pending-validate"><?php echo $Events->loading(); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="subtitle link padding10 padding2">
                        <?php echo __('Active events (8 hrs)'); ?></b> <a href=""><?php echo __('Info'); ?></a>
                    </div>
                    <?php echo $Events->getDataTableEvents(); ?>
                </div>
            </div>
        </div>

    </div>
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
                        <?php echo __('Top 20 groups'); ?></b> <a href=""><?php echo __('More details'); ?></a>
                    </div>
                    <?php echo $Agents->getDataTableGroups(); ?>
                </div>
                <div class="col-6">
                    <div class="subtitle link padding10 padding2">
                        <?php echo __('Operating system'); ?></b> <a href=""><?php echo __('Info'); ?></a>
                    </div>
                    <?php echo $Agents->getOperatingSystemGraph(); ?>
                    <div class="subtitle link padding10 padding2 br-t">
                        <?php echo __('Status'); ?></b> <a href=""><?php echo __('Info'); ?></a>
                    </div>
                    <?php echo $Agents->getStatusGraph(); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="container mrgn_5px" id="Agents">
            <div class="title br-b">
                <?php echo $Configurations->title; ?>
            </div>
            <div class="row br-b flex-nowrap">
                <div class="col-3 flex flex-column center pdd_20px br-r">
                    <?php echo $Configurations->getTotalGroups(); ?>
                </div>
                <div class="col-3 flex flex-column center pdd_20px br-r">
                    <?php echo $Configurations->getTotalModules(); ?>
                </div>
                <div class="col-3 flex flex-column center pdd_20px br-r">
                    <?php echo $Configurations->getTotalPolicies(); ?>
                </div>
                <div class="col-3 flex flex-column center pdd_20px">
                    <?php echo $Configurations->getTotalRemotePlugins(); ?>
                </div>
            </div>
            <div class="row flex-nowrap">
                <div class="col-4 flex flex-column center pdd_20px br-r">
                    <?php echo $Configurations->getTotalModuleTemplate(); ?>
                </div>
                <div class="col-4 flex flex-column center pdd_20px br-r">
                    <?php echo $Configurations->getNotInitModules(); ?>
                </div>
                <div class="col-4 flex flex-column center pdd_20px">
                    <?php echo $Configurations->getTotalUnknowAgents(); ?>
                </div>
            </div>
        </div>
        <div class="container mrgn_5px">
            <div class="title br-b">
                <?php echo $ScheduledDowntime->title; ?>
            </div>
            <?php echo $ScheduledDowntime->list(); ?>
        </div>
    </div>
</div>
<?php
echo $javascript;
