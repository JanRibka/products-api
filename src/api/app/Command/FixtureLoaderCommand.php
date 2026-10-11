<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(
  name: 'fixtures:load',
  description: 'Loads fixture data into the database'
)]
class FixtureLoaderCommand extends Command
{
  public function __construct(
    private EntityManagerInterface $entityManager
  ) {
    parent::__construct();
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $helper = $this->getHelper('question');
    $question = new ConfirmationQuestion(
      '<question>This will overwrite data in the database with fixtures. Do you want to continue? (y/N) </question>',
      false
    );

    if (!$helper instanceof QuestionHelper) {
      throw new \RuntimeException('Symfony QuestionHelper not found');
    }

    if (!$helper->ask($input, $output, $question)) {
      $output->writeln('<comment>Aborted. No changes were made.</comment>');

      return Command::SUCCESS;
    }

    $loader = require __DIR__ . '/../Fixture/FixtureLoader.php';

    if (!$loader instanceof Loader) {
      throw new \RuntimeException('Fixture loader not found');
    }

    foreach ($loader->getFixtures() as $fixture) {
      $output->writeln('Running fixture: ' . get_class($fixture));
    }

    $executor = new ORMExecutor($this->entityManager, new ORMPurger());
    $executor->execute($loader->getFixtures());

    $output->writeln('<info>All fixtures have been successfully loaded into the database.</info>');

    return Command::SUCCESS;
  }
}
