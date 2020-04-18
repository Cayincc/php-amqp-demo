<?php

\Swoole\Coroutine\run(function () {
    for ($i = 0; $i < 20; $i++) {
        \Swoole\Coroutine::create(function() use ($i) {
            \Swoole\Coroutine::sleep(1);
            echo "{$i}\n";
        });
    }

    \Swoole\Coroutine::create(function() {
        \Swoole\Coroutine::sleep(1);
        echo "done\n";
    });
});

echo 123;