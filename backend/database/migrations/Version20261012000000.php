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

/**
 * DBAL 4.5 schema objects are immutable, while Doctrine Migrations still passes up() a Schema to mutate, so the
 * tables are built with the schema editors and created through the platform's SQL.
 *
 * Index and foreign key names are the ones Doctrine generates for the entity mapping, so that
 * doctrine:migrations:diff reports no changes after this migration has run.
 */
final class Version20261012000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Partners, campaigns and applicants';
    }

    public function up(Schema $schema): void
    {
        $partners = Table::editor()
            ->setUnquotedName('partners')
            ->setColumns(
                $this->column('id', Types::GUID),
                $this->column('name', Types::STRING, length: 255),
                $this->column('api_token_hash', Types::STRING, length: 64),
            )
            ->setPrimaryKeyConstraint($this->primaryKey())
            ->setIndexes($this->index('UNIQ_EFEB516474162BCB', ['api_token_hash'], IndexType::UNIQUE))
            ->create();

        $campaigns = Table::editor()
            ->setUnquotedName('campaigns')
            ->setColumns(
                $this->column('id', Types::GUID),
                $this->column('partner_id', Types::GUID),
                $this->column('name', Types::STRING, length: 255),
                $this->column('eligibility_threshold', Types::SMALLINT, default: 70),
            )
            ->setPrimaryKeyConstraint($this->primaryKey())
            ->setIndexes($this->index('IDX_E37374709393F8FE', ['partner_id']))
            ->setForeignKeyConstraints($this->foreignKey('FK_E37374709393F8FE', 'partner_id', 'partners'))
            ->create();

        $applicants = Table::editor()
            ->setUnquotedName('applicants')
            ->setColumns(
                $this->column('id', Types::GUID),
                $this->column('campaign_id', Types::GUID),
                $this->column('full_name', Types::STRING, length: 255),
                $this->column('email', Types::STRING, length: 255),
                $this->column('ai_score', Types::SMALLINT, notNull: false),
                $this->column('highlights', Types::JSON),
                $this->column('status', Types::STRING, length: 20),
                $this->column('applied_at', Types::DATETIME_IMMUTABLE),
            )
            ->setPrimaryKeyConstraint($this->primaryKey())
            ->setIndexes(
                $this->index('IDX_7FAFCADBF639F774', ['campaign_id']),
                $this->index('applicants_campaign_score_idx', ['campaign_id', 'ai_score']),
            )
            ->setForeignKeyConstraints($this->foreignKey('FK_7FAFCADBF639F774', 'campaign_id', 'campaigns'))
            ->create();

        foreach ($this->platform->getCreateTablesSQL([$partners, $campaigns, $applicants]) as $sql) {
            $this->addSql($sql);
        }
    }

    public function down(Schema $schema): void
    {
        foreach (['applicants', 'campaigns', 'partners'] as $table) {
            $this->addSql($this->platform->getDropTableSQL($table));
        }
    }

    /**
     * @param  non-empty-string  $name
     */
    private function column(string $name, string $type, ?int $length = null, bool $notNull = true, mixed $default = null): Column
    {
        return Column::editor()
            ->setUnquotedName($name)
            ->setTypeName($type)
            ->setLength($length)
            ->setNotNull($notNull)
            ->setDefaultValue($default)
            ->create();
    }

    private function primaryKey(): PrimaryKeyConstraint
    {
        return PrimaryKeyConstraint::editor()->setUnquotedColumnNames('id')->create();
    }

    /**
     * @param  non-empty-string  $name
     * @param  non-empty-list<non-empty-string>  $columns
     */
    private function index(string $name, array $columns, IndexType $type = IndexType::REGULAR): Index
    {
        return Index::editor()->setUnquotedName($name)->setUnquotedColumnNames(...$columns)->setType($type)->create();
    }

    /**
     * @param  non-empty-string  $name
     * @param  non-empty-string  $column
     * @param  non-empty-string  $referencedTable
     */
    private function foreignKey(string $name, string $column, string $referencedTable): ForeignKeyConstraint
    {
        return ForeignKeyConstraint::editor()
            ->setUnquotedName($name)
            ->setUnquotedReferencingColumnNames($column)
            ->setUnquotedReferencedTableName($referencedTable)
            ->setUnquotedReferencedColumnNames('id')
            ->create();
    }
}
