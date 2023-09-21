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
                                    <?php echo $Overview->getLicenseUsage(); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 br-l relative">
                            <div class="subtitle link padding10 padding2">
                                <?php echo __('XML packets processed (last 24 hrs)'); ?> <a href=""><?php echo __('Info'); ?></a>
                            </div>
                            <?php echo $Overview->getXmlProcessed(); ?>
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
                    <div class="row">
                        <div class="col-6">
                            <div class="subtitle link padding10 padding2">
                                <?php echo __('Tags'); ?> <a href=""><?php echo __('Info'); ?></a>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="subtitle link padding10 padding2">
                                <?php echo __('By modules groups'); ?> <a href=""><?php echo __('Info'); ?></a>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="subtitle link padding10 padding2">
                                <?php echo __('Status'); ?> <a href=""><?php echo __('Info'); ?></a>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="subtitle link padding10 padding2">
                                <?php echo __('Groups'); ?> <a href=""><?php echo __('Info'); ?></a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="col-5">

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
    </div>
</div>

<div class="row">

</div>

<div class="row">

</div>