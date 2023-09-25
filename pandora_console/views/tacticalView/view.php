<div id="welcome-message">
    <?php echo $welcome; ?>
    <span class="subtitle-welcome-message"><?php echo __('This is the latest data in your tactical view'); ?></span>
</div>

<div class="row">
    <div class="col-6">
        <div id="general-overview">
            <div class="container">
                <div class="title">
                    <?php echo $Overview->title; ?>
                </div>
                <div class="content br-t">
                    <div class="row">
                        <div class="col-6">
                            <div class="row">
                                <div class="col-6">
                                    <div class="padding10">
                                        <span class="subtitle">
                                            <?php echo __('Pandora FMS log size'); ?>
                                        </span>
                                        <?php echo $Overview->getLogSizeStatus(); ?>
                                    </div>
                                </div>
                                <div class="col-6 br-l">
                                    <div class="padding10">
                                        <span class="subtitle">
                                            <?php echo __('Wux server status'); ?>
                                        </span>
                                        <?php echo $Overview->getWuxServerStatus(); ?>
                                    </div>
                                </div>
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
            <div class="col-7">
                <div class="container">
                    <div class="title">
                        <?php echo $MonitoringElements->title; ?>
                    </div>
                    <div class="content br-t">
                        <div class="row">
                            <div class="col-6 br-r br-b">
                                <div class="subtitle link padding10 padding2">
                                    <?php echo __('Tags'); ?> <a href=""><?php echo __('Info'); ?></a>
                                </div>
                                <?php echo $MonitoringElements->getTagsGraph(); ?>
                            </div>
                            <div class="col-6 br-b">
                                <div class="subtitle link padding10 padding2">
                                    <?php echo __('By modules groups'); ?> <a href=""><?php echo __('Info'); ?></a>
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
                                    <?php echo __('Groups'); ?> <a href=""><?php echo __('Info'); ?></a>
                                </div>
                                <?php echo $MonitoringElements->getAgentGroupsGraph(); ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="col-5">
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
                                <?php echo __('Reads (last 24 hrs)'); ?> <a href=""><?php echo __('Info'); ?></a>
                            </div>
                            <?php echo $Database->getWritesGraph(); ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <div class="col-6">
        <div class="container">
            <div class="title">
                <?php echo $NewsBoard->title; ?>
            </div>
            <?php echo $NewsBoard->getNews(); ?>
        </div>
        <div class="row">
            <?php if ($Groups->total < 200) : ?>
                <div class="col-6">
                    <div class="container">
                        <div class="title br-b">
                            <?php echo $Groups->title; ?>
                        </div>
                        <div class="subtitle link padding10 padding2">
                            <?php echo __('Status'); ?> <a href=""><?php echo __('Info'); ?></a>
                        </div>
                        <?php echo $Groups->getStatusHeatMap(); ?>
                    </div>
                </div>
            <?php endif; ?>
            <div class="col-6">
                <div class="container" id="logStorage">
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
                <div class="container" id="SNMPTraps">
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
        </div>
    </div>
</div>

<div class="row">

</div>

<div class="row">

</div>