<?php

namespace Tests\Concerns;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

trait RefreshesDoctrineSchema
{
    protected EntityManagerInterface $em;

    // Each test boots a new app with a new in-memory SQLite connection, so creating the schema is enough.
    protected function setUpRefreshesDoctrineSchema(): void
    {
        $this->em = $this->app->make(EntityManagerInterface::class);
        (new SchemaTool($this->em))->createSchema($this->em->getMetadataFactory()->getAllMetadata());
    }
}
