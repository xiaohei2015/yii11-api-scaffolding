<?php /* @var $this Controller */ ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="cn" lang="cn">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="language" content="cn" />
    <meta name="keywords" content="" />
    <meta name="description" content="" />
    <title><?php echo CHtml::encode($this->pageTitle); ?></title>
    <?php Yii::app()->clientScript->registerCoreScript('jquery'); ?>

    <?php if($this->environment==3) { ?>
        <script  src="/js/tc_dev.js?V=<?php echo Yii::app()->params['ResourceVersion']; ?>"></script>
    <?php }else if($this->environment==2) { ?>
        <script  src="/js/tc_dev.js?V=<?php echo Yii::app()->params['ResourceVersion']; ?>"></script>
    <?php }else if($this->environment==1)  { ?>
        <script  src="/js/tc_ol.js?V=<?php echo Yii::app()->params['ResourceVersion']; ?>"></script>
    <?php } ?>

    <script>
        scaffoldingLogger.memId = "<?php echo Yii::app()->user->id; ?>";
        scaffoldingLogger.tracking();
    </script>
</head>
	<?php echo $content; ?>
</html>
