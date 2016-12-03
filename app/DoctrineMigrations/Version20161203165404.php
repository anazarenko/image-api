<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161203165404 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE images (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, image VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, hashtag VARCHAR(255) DEFAULT NULL, latitude DOUBLE PRECISION NOT NULL, longitude DOUBLE PRECISION NOT NULL, small_image_path VARCHAR(255) DEFAULT NULL, big_image_path VARCHAR(255) DEFAULT NULL, weather VARCHAR(255) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, modified_at DATETIME NOT NULL, INDEX IDX_E01FBE6AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(100) DEFAULT NULL, email VARCHAR(60) NOT NULL, password VARCHAR(64) NOT NULL, token VARCHAR(255) NOT NULL, avatar VARCHAR(255) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, modified_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), UNIQUE INDEX UNIQ_1483A5E95F37A13B (token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE images ADD CONSTRAINT FK_E01FBE6AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE images DROP FOREIGN KEY FK_E01FBE6AA76ED395');
        $this->addSql('DROP TABLE images');
        $this->addSql('DROP TABLE users');
    }
}
