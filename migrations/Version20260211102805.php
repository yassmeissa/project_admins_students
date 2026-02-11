<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211102805 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE post_forum DROP FOREIGN KEY FK_123032221AD8D010');
        $this->addSql('ALTER TABLE post_forum DROP FOREIGN KEY FK_1230322294F4A9D2');
        $this->addSql('ALTER TABLE post_forum DROP FOREIGN KEY FK_12303222F9295384');
        $this->addSql('DROP INDEX IDX_123032221AD8D010 ON post_forum');
        $this->addSql('DROP INDEX IDX_1230322294F4A9D2 ON post_forum');
        $this->addSql('DROP INDEX IDX_12303222F9295384 ON post_forum');
        $this->addSql('ALTER TABLE post_forum ADD student_id INT DEFAULT NULL, ADD course_id INT DEFAULT NULL, DROP students_id, DROP themes_id, DROP courses_id');
        $this->addSql('ALTER TABLE post_forum ADD CONSTRAINT FK_12303222CB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE post_forum ADD CONSTRAINT FK_12303222591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
        $this->addSql('CREATE INDEX IDX_12303222CB944F1A ON post_forum (student_id)');
        $this->addSql('CREATE INDEX IDX_12303222591CC992 ON post_forum (course_id)');
        $this->addSql('ALTER TABLE question CHANGE answers answers JSON NOT NULL');
        $this->addSql('ALTER TABLE theme ADD image_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE theme DROP image_url');
        $this->addSql('ALTER TABLE question CHANGE answers answers JSON NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE post_forum DROP FOREIGN KEY FK_12303222CB944F1A');
        $this->addSql('ALTER TABLE post_forum DROP FOREIGN KEY FK_12303222591CC992');
        $this->addSql('DROP INDEX IDX_12303222CB944F1A ON post_forum');
        $this->addSql('DROP INDEX IDX_12303222591CC992 ON post_forum');
        $this->addSql('ALTER TABLE post_forum ADD students_id INT DEFAULT NULL, ADD themes_id INT DEFAULT NULL, ADD courses_id INT DEFAULT NULL, DROP student_id, DROP course_id');
        $this->addSql('ALTER TABLE post_forum ADD CONSTRAINT FK_123032221AD8D010 FOREIGN KEY (students_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE post_forum ADD CONSTRAINT FK_1230322294F4A9D2 FOREIGN KEY (themes_id) REFERENCES theme (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE post_forum ADD CONSTRAINT FK_12303222F9295384 FOREIGN KEY (courses_id) REFERENCES course (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_123032221AD8D010 ON post_forum (students_id)');
        $this->addSql('CREATE INDEX IDX_1230322294F4A9D2 ON post_forum (themes_id)');
        $this->addSql('CREATE INDEX IDX_12303222F9295384 ON post_forum (courses_id)');
    }
}
