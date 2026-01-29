<?php

/*
 * This file is part of the "Remote Work" plugin for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RemoteWorkBundle\Migrations;

use App\Doctrine\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * @version 1.0.0
 */
final class Version20260128000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates the table to store remote work entries (homeoffice, business trips)';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('kimai2_remote_work');

        $table->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
        $table->addColumn('user_id', 'integer', ['notnull' => true]);
        $table->addColumn('type', 'string', ['notnull' => true, 'length' => 30]);
        $table->addColumn('date', 'date', ['notnull' => true]);
        $table->addColumn('half_day', 'boolean', ['notnull' => true, 'default' => false]);
        $table->addColumn('comment', 'string', ['notnull' => true, 'length' => 250, 'default' => '']);
        $table->addColumn('status', 'string', ['notnull' => true, 'length' => 20, 'default' => 'new']);
        $table->addColumn('created_by', 'integer', ['notnull' => false, 'default' => null]);
        $table->addColumn('created_date', 'datetime', ['notnull' => true]);
        $table->addColumn('approved_by', 'integer', ['notnull' => false, 'default' => null]);
        $table->addColumn('approved_date', 'datetime', ['notnull' => false, 'default' => null]);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_REMOTE_WORK_USER');
        $table->addIndex(['created_by'], 'IDX_REMOTE_WORK_CREATED_BY');
        $table->addIndex(['approved_by'], 'IDX_REMOTE_WORK_APPROVED_BY');
        $table->addIndex(['user_id', 'date'], 'IDX_REMOTE_WORK_USER_DATE');

        $table->addForeignKeyConstraint('kimai2_users', ['user_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_REMOTE_WORK_USER');
        $table->addForeignKeyConstraint('kimai2_users', ['created_by'], ['id'], ['onDelete' => 'SET NULL'], 'FK_REMOTE_WORK_CREATED_BY');
        $table->addForeignKeyConstraint('kimai2_users', ['approved_by'], ['id'], ['onDelete' => 'SET NULL'], 'FK_REMOTE_WORK_APPROVED_BY');
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('kimai2_remote_work');
        $table->removeForeignKey('FK_REMOTE_WORK_USER');
        $table->removeForeignKey('FK_REMOTE_WORK_CREATED_BY');
        $table->removeForeignKey('FK_REMOTE_WORK_APPROVED_BY');
        $schema->dropTable('kimai2_remote_work');
    }
}
