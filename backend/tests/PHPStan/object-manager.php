<?php

// Gives phpstan-doctrine the app's entity manager, so it reads the real mapping and repository classes.

use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Contracts\Console\Kernel;

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

return $app->make(EntityManagerInterface::class);
