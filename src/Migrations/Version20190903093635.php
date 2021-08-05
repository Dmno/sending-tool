<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190903093635 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE batch (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, contact_list_id INT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, visible TINYINT(1) NOT NULL, mode VARCHAR(255) DEFAULT NULL, setup TINYINT(1) NOT NULL, cost DOUBLE PRECISION NOT NULL, revenue DOUBLE PRECISION NOT NULL, INDEX IDX_F80B52D4A76ED395 (user_id), INDEX IDX_F80B52D4A781370A (contact_list_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE task (id INT AUTO_INCREMENT NOT NULL, server_id INT NOT NULL, campaign_id INT NOT NULL, import_id INT NOT NULL, status VARCHAR(255) NOT NULL, progress INT NOT NULL, sent INT NOT NULL, opens INT NOT NULL, bounces INT NOT NULL, campaign_uid VARCHAR(255) DEFAULT NULL, INDEX IDX_527EDB251844E6B7 (server_id), INDEX IDX_527EDB25F639F774 (campaign_id), INDEX IDX_527EDB25B6A263D9 (import_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaign_content (id INT AUTO_INCREMENT NOT NULL, from_name VARCHAR(255) NOT NULL, subject_line VARCHAR(255) NOT NULL, template LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaign (id INT AUTO_INCREMENT NOT NULL, campaign_content_id INT DEFAULT NULL, batch_id INT NOT NULL, speed INT NOT NULL, INDEX IDX_1F1512DD43B78345 (campaign_content_id), INDEX IDX_1F1512DDF39EBE7A (batch_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE contact_list (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, name VARCHAR(255) NOT NULL, size INT NOT NULL, file_name VARCHAR(255) NOT NULL, updated_at DATETIME NOT NULL, shared TINYINT(1) NOT NULL, visible TINYINT(1) NOT NULL, INDEX IDX_6C377AE7A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE warmup_plan (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, name VARCHAR(255) NOT NULL, plan LONGTEXT NOT NULL COMMENT \'(DC2Type:simple_array)\', shared TINYINT(1) NOT NULL, visible TINYINT(1) NOT NULL, INDEX IDX_E42FE9DDA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE import (id INT AUTO_INCREMENT NOT NULL, contact_list_id INT NOT NULL, offset INT NOT NULL, length INT NOT NULL, list_uid VARCHAR(255) DEFAULT NULL, progress INT NOT NULL, INDEX IDX_9D4ECE1DA781370A (contact_list_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE server (id INT AUTO_INCREMENT NOT NULL, batch_id INT NOT NULL, current_task_id INT DEFAULT NULL, ip VARCHAR(255) NOT NULL, retry INT NOT NULL, retry_at DATETIME DEFAULT NULL, dead TINYINT(1) NOT NULL, INDEX IDX_5A6DD5F6F39EBE7A (batch_id), UNIQUE INDEX UNIQ_5A6DD5F627BB8403 (current_task_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, name VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE batch ADD CONSTRAINT FK_F80B52D4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE batch ADD CONSTRAINT FK_F80B52D4A781370A FOREIGN KEY (contact_list_id) REFERENCES contact_list (id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB251844E6B7 FOREIGN KEY (server_id) REFERENCES server (id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25F639F774 FOREIGN KEY (campaign_id) REFERENCES campaign (id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id)');
        $this->addSql('ALTER TABLE campaign ADD CONSTRAINT FK_1F1512DD43B78345 FOREIGN KEY (campaign_content_id) REFERENCES campaign_content (id)');
        $this->addSql('ALTER TABLE campaign ADD CONSTRAINT FK_1F1512DDF39EBE7A FOREIGN KEY (batch_id) REFERENCES batch (id)');
        $this->addSql('ALTER TABLE contact_list ADD CONSTRAINT FK_6C377AE7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE warmup_plan ADD CONSTRAINT FK_E42FE9DDA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE import ADD CONSTRAINT FK_9D4ECE1DA781370A FOREIGN KEY (contact_list_id) REFERENCES contact_list (id)');
        $this->addSql('ALTER TABLE server ADD CONSTRAINT FK_5A6DD5F6F39EBE7A FOREIGN KEY (batch_id) REFERENCES batch (id)');
        $this->addSql('ALTER TABLE server ADD CONSTRAINT FK_5A6DD5F627BB8403 FOREIGN KEY (current_task_id) REFERENCES task (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE campaign DROP FOREIGN KEY FK_1F1512DDF39EBE7A');
        $this->addSql('ALTER TABLE server DROP FOREIGN KEY FK_5A6DD5F6F39EBE7A');
        $this->addSql('ALTER TABLE server DROP FOREIGN KEY FK_5A6DD5F627BB8403');
        $this->addSql('ALTER TABLE campaign DROP FOREIGN KEY FK_1F1512DD43B78345');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25F639F774');
        $this->addSql('ALTER TABLE batch DROP FOREIGN KEY FK_F80B52D4A781370A');
        $this->addSql('ALTER TABLE import DROP FOREIGN KEY FK_9D4ECE1DA781370A');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25B6A263D9');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB251844E6B7');
        $this->addSql('ALTER TABLE batch DROP FOREIGN KEY FK_F80B52D4A76ED395');
        $this->addSql('ALTER TABLE contact_list DROP FOREIGN KEY FK_6C377AE7A76ED395');
        $this->addSql('ALTER TABLE warmup_plan DROP FOREIGN KEY FK_E42FE9DDA76ED395');
        $this->addSql('DROP TABLE batch');
        $this->addSql('DROP TABLE task');
        $this->addSql('DROP TABLE campaign_content');
        $this->addSql('DROP TABLE campaign');
        $this->addSql('DROP TABLE contact_list');
        $this->addSql('DROP TABLE warmup_plan');
        $this->addSql('DROP TABLE import');
        $this->addSql('DROP TABLE server');
        $this->addSql('DROP TABLE user');
    }
}
