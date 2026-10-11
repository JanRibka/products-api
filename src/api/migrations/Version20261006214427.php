<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Initial schema: brand, material and product.
 */
final class Version20261006214427 extends AbstractMigration
{
  public function getDescription(): string
  {
    return 'Create brand, material and product tables';
  }

  public function up(Schema $schema): void
  {
    $this->addSql('CREATE TABLE brand (
      id INT AUTO_INCREMENT NOT NULL,
      name VARCHAR(100) NOT NULL,
      UNIQUE INDEX UNIQ_1C52F9585E237E06 (name),
      PRIMARY KEY(id)
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

    $this->addSql('CREATE TABLE material (
      id INT AUTO_INCREMENT NOT NULL,
      name VARCHAR(100) NOT NULL,
      UNIQUE INDEX UNIQ_16F4A9A65E237E06 (name),
      PRIMARY KEY(id)
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

    $this->addSql('CREATE TABLE product (
      id INT AUTO_INCREMENT NOT NULL,
      code VARCHAR(20) NOT NULL,
      name VARCHAR(255) NOT NULL,
      description LONGTEXT DEFAULT NULL,
      price NUMERIC(10, 2) NOT NULL,
      brandId INT NOT NULL,
      materialId INT NOT NULL,
      createdAt DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
      updatedAt DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
      UNIQUE INDEX UNIQ_D34A04AD77153098 (code),
      INDEX IDX_D34A04AD9E2E8BA3 (brandId),
      INDEX IDX_D34A04ADE9764D19 (materialId),
      PRIMARY KEY(id)
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

    $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD9E2E8BA3 FOREIGN KEY (brandId) REFERENCES brand (id) ON DELETE RESTRICT');
    $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADE9764D19 FOREIGN KEY (materialId) REFERENCES material (id) ON DELETE RESTRICT');
  }

  public function down(Schema $schema): void
  {
    $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD9E2E8BA3');
    $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04ADE9764D19');
    $this->addSql('DROP TABLE product');
    $this->addSql('DROP TABLE material');
    $this->addSql('DROP TABLE brand');
  }

  public function isTransactional(): bool
  {
    return false;
  }
}
