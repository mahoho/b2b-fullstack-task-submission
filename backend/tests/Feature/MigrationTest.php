<?php

namespace Tests\Feature;

use Database\Migrations\Version20261012000000;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Psr\Log\NullLogger;
use Tests\TestCase;

class MigrationTest extends TestCase
{
    public function test_migration_uses_no_deprecated_schema_api(): void
    {
        // Doctrine Migrations loads migrations from their directory; they are not in the Composer autoloader.
        require_once database_path('migrations/Version20261012000000.php');
        $connection = $this->app->make(EntityManagerInterface::class)->getConnection();

        // Called directly so only deprecations raised by our migration count, not ones inside doctrine/migrations itself.
        Deprecation::enableTrackingDeprecations();
        $before = Deprecation::getTriggeredDeprecations();

        $schema = new Schema();
        (new Version20261012000000($connection, new NullLogger()))->up($schema);
        (new Version20261012000000($connection, new NullLogger()))->down($schema);

        $this->assertSame($before, Deprecation::getTriggeredDeprecations());
    }

    public function test_migrated_schema_matches_the_entity_mapping(): void
    {
        $this->artisan('doctrine:migrations:migrate', ['--no-interaction' => true])->assertSuccessful();

        $em = $this->app->make(EntityManagerInterface::class);
        $pending = array_filter(
            (new SchemaTool($em))->getUpdateSchemaSql($em->getMetadataFactory()->getAllMetadata()),
            // The migrations bookkeeping table is not mapped, so its DROP is expected.
            fn (string $sql) => ! str_contains($sql, 'migrations'),
        );

        $this->assertSame([], array_values($pending));
    }
}
