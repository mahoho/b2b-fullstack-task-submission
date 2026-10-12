<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261012000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Partners, campaigns and applicants';
    }

    public function up(Schema $schema): void
    {
        $partners = $schema->createTable('partners');
        $partners->addColumn('id', 'guid');
        $partners->addColumn('name', 'string', ['length' => 255]);
        $partners->addColumn('api_token_hash', 'string', ['length' => 64]);
        $partners->setPrimaryKey(['id']);
        $partners->addUniqueIndex(['api_token_hash']);

        $campaigns = $schema->createTable('campaigns');
        $campaigns->addColumn('id', 'guid');
        $campaigns->addColumn('partner_id', 'guid');
        $campaigns->addColumn('name', 'string', ['length' => 255]);
        $campaigns->addColumn('eligibility_threshold', 'smallint', ['default' => 70]);
        $campaigns->setPrimaryKey(['id']);
        $campaigns->addIndex(['partner_id']);
        $campaigns->addForeignKeyConstraint('partners', ['partner_id'], ['id']);

        $applicants = $schema->createTable('applicants');
        $applicants->addColumn('id', 'guid');
        $applicants->addColumn('campaign_id', 'guid');
        $applicants->addColumn('full_name', 'string', ['length' => 255]);
        $applicants->addColumn('email', 'string', ['length' => 255]);
        $applicants->addColumn('ai_score', 'smallint', ['notnull' => false]);
        $applicants->addColumn('highlights', 'json');
        $applicants->addColumn('status', 'string', ['length' => 20]);
        $applicants->addColumn('applied_at', 'datetime_immutable');
        $applicants->setPrimaryKey(['id']);
        $applicants->addIndex(['campaign_id']);
        $applicants->addIndex(['campaign_id', 'ai_score'], 'applicants_campaign_score_idx');
        $applicants->addForeignKeyConstraint('campaigns', ['campaign_id'], ['id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('applicants');
        $schema->dropTable('campaigns');
        $schema->dropTable('partners');
    }
}
