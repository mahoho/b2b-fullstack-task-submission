<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Index\IndexType;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20261012000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Partners, campaigns and applicants';
    }

    public function up(Schema $schema): void
    {
        $schema->addTable(
            Table::editor()
                ->setUnquotedName('partners')
                ->setColumns(
                    $this->col('id', Types::GUID),
                    $this->col('name', Types::STRING, 255),
                    $this->col('api_token_hash', Types::STRING, 64),
                )
                ->setPrimaryKeyConstraint($this->pk())
                ->setIndexes(
                    Index::editor()
                        ->setUnquotedName('uniq_partners_api_token_hash')
                        ->setUnquotedColumnNames('api_token_hash')
                        ->setType(IndexType::UNIQUE)
                        ->create(),
                )
                ->create(),
        );

        $schema->addTable(
            Table::editor()
                ->setUnquotedName('campaigns')
                ->setColumns(
                    $this->col('id', Types::GUID),
                    $this->col('partner_id', Types::GUID),
                    $this->col('name', Types::STRING, 255),
                    Column::editor()
                        ->setUnquotedName('eligibility_threshold')
                        ->setTypeName(Types::SMALLINT)
                        ->setDefaultValue(70)
                        ->create(),
                )
                ->setPrimaryKeyConstraint($this->pk())
                ->setIndexes($this->idx('idx_campaigns_partner_id', 'partner_id'))
                ->setForeignKeyConstraints($this->fk('partner_id', 'partners'))
                ->create(),
        );

        $schema->addTable(
            Table::editor()
                ->setUnquotedName('applicants')
                ->setColumns(
                    $this->col('id', Types::GUID),
                    $this->col('campaign_id', Types::GUID),
                    $this->col('full_name', Types::STRING, 255),
                    $this->col('email', Types::STRING, 255),
                    Column::editor()
                        ->setUnquotedName('ai_score')
                        ->setTypeName(Types::SMALLINT)
                        ->setNotNull(false)
                        ->create(),
                    $this->col('highlights', Types::JSON),
                    $this->col('status', Types::STRING, 20),
                    $this->col('applied_at', Types::DATETIME_IMMUTABLE),
                )
                ->setPrimaryKeyConstraint($this->pk())
                ->setIndexes(
                    $this->idx('idx_applicants_campaign_id', 'campaign_id'),
                    $this->idx('applicants_campaign_score_idx', 'campaign_id', 'ai_score'),
                )
                ->setForeignKeyConstraints($this->fk('campaign_id', 'campaigns'))
                ->create(),
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE applicants');
        $this->addSql('DROP TABLE campaigns');
        $this->addSql('DROP TABLE partners');
    }

    private function col(string $name, string $type, ?int $length = null): Column
    {
        $editor = Column::editor()->setUnquotedName($name)->setTypeName($type);

        if ($length !== null) {
            $editor->setLength($length);
        }

        return $editor->create();
    }

    private function pk(): PrimaryKeyConstraint
    {
        return PrimaryKeyConstraint::editor()->setUnquotedColumnNames('id')->create();
    }

    private function idx(string $name, string ...$columns): Index
    {
        return Index::editor()->setUnquotedName($name)->setUnquotedColumnNames(...$columns)->create();
    }

    private function fk(string $column, string $table): ForeignKeyConstraint
    {
        return ForeignKeyConstraint::editor()
            ->setUnquotedReferencingColumnNames($column)
            ->setUnquotedReferencedTableName($table)
            ->setUnquotedReferencedColumnNames('id')
            ->create();
    }
}
