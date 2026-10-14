<!DOCTYPE html>
<html lang="en" <?php echo $__env->yieldContent('html-attribute'); ?>>

<head>
    <?php echo $__env->make('layouts.partials/title-meta', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php echo $__env->make('layouts.partials/head-css', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</head>

<body>

    <div class="app-wrapper">

        <?php echo $__env->make('layouts.partials/sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <?php echo $__env->make('layouts.partials/topbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div class="page-content">

            <div class="container-fluid">

                <?php echo $__env->yieldContent('content'); ?>

            </div>

            <?php echo $__env->make('layouts.partials/footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>

    </div>

    <?php echo $__env->make('layouts.partials/vendor-scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <?php echo $__env->yieldPushContent('scripts'); ?>

</body>

</html><?php /**PATH F:\Personal Projects\Elevator\switch-smarter\resources\views/layouts/vertical.blade.php ENDPATH**/ ?>