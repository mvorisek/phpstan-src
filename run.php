<?php

// run command
// phpw . -d disable_functions= run.php

$runPhpStanWithTracing = function(string $resPath, bool $withOpcache) {
    if (is_file($resPath . '.xt')) {
        unlink($resPath . '.xt');
    }

    passthru('cd "' . __DIR__ . '/ui' . '" & phpw .. ../bin/phpstan clear-result-cache');

    $cmd = 'cd "' . __DIR__ . '/ui' . '" & phpw .. 1GB --with-xdebug'
        . ' -d opcache.enable=' . ($withOpcache ? 1 : 0)
        . ' -d opcache.cache_id=816'
        . ' -d xdebug.auto_trace=' . (basename($resPath) === 't_discard' ? 0 : 1)
        . ' -d xdebug.collect_params=3' // use 1 for types only
        . ' -d xdebug.collect_return=1'
        //. ' -d xdebug.collect_assignments=1'
        . ' -d xdebug.var_display_max_depth=0'
        . ' -d xdebug.trace_output_dir="' . dirname($resPath) . '"'
        . ' -d xdebug.trace_output_name="' . basename($resPath) . '"'
        . ' ../bin/phpstan analyse'; //--xdebug --debug --verbose
    echo '----- running:' . "\n" . $cmd . "\n";
    $t = microtime(true);
    passthru($cmd);
    echo '----- finished in: ' . (microtime(true) - $t)  . " seconds\n\n";
};

$analInDir = __DIR__ . '/anal/in';

$runPhpStanWithTracing($analInDir . '/t_discard', true); // discard first run
exit;
$runPhpStanWithTracing($analInDir . '/t_discard', false); // discard first run

$runPhpStanWithTracing($analInDir . '/t_c0', false);
$runPhpStanWithTracing($analInDir . '/t_c1', true);

//$runPhpStanWithTracing($analInDir . '/t_c0_2nd', false);
//$runPhpStanWithTracing($analInDir . '/t_c1_2nd', true);
