<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260403161238 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE merge_queue (id INT AUTO_INCREMENT NOT NULL, files LONGTEXT NOT NULL, status VARCHAR(20) NOT NULL, output_file VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, processed_at DATETIME DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_5EFBB608A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE merge_queue ADD CONSTRAINT FK_5EFBB608A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE merge_queue DROP FOREIGN KEY FK_5EFBB608A76ED395');
        $this->addSql('DROP TABLE merge_queue');
    }
}
