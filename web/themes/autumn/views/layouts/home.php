<?php /* @var $this Controller */ ?>
<?php $this->beginContent('//layouts/main'); ?>
    <body id="evo20150609">

            <?php if($this->getEnvironment()==3) { ?>
                <span>开发环境</span>
            <?php }else if($this->getEnvironment()==2) { ?>
                <span>测试环境</span>
            <?php }else if($this->getEnvironment()==1)  { ?>
                <span>测试版</span>
            <?php } ?>
            <hr>

            <?php echo $content; ?>

    </body>
<?php $this->endContent(); ?>