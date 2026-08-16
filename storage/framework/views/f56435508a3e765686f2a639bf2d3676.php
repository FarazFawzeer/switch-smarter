<!DOCTYPE html>
<html <?php echo $__env->yieldContent('html-attribute'); ?>>

<head>
    <?php echo $__env->make('layouts.partials/title-meta', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php echo $__env->make('layouts.partials/head-css', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</head>

<body <?php echo $__env->yieldContent('body-attribuet'); ?>>

    <?php echo $__env->yieldContent('content'); ?>

    <?php echo $__env->make('layouts.partials/vendor-scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

</body>

</html><?php /**PATH F:\Personal Projects\Elevator\switch-smarter\resources\views/layouts/base.blade.php ENDPATH**/ ?>