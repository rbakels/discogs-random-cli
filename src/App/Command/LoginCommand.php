<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;

use App\Lib\Discogs;
use App\Lib\Authentication;

error_reporting(E_ALL ^ E_WARNING ^ E_DEPRECATED); 

class LoginCommand extends Command
{
    protected static $defaultName = 'login';

    protected function configure(): void
    {
        $this
            ->setDescription('Authenticate to Discogs')
            ->setHelp('Authenticate to Discogs using a username and a personalized authentication token')
            ->addArgument('username', InputArgument::OPTIONAL, 'Discogs username')
            ->addArgument('token', InputArgument::OPTIONAL, 'Discogs personalized authentication token')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force overwrite authentication settings');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $authentication = new Authentication($output);

        $username = '';
        $token = '';

        if ($authentication->hasAuthentication() && !$input->getOption('force')) {
            $output->writeln("There is already an authentication saved. To overwrite, use the --force/-f option.");

            return Command::SUCCESS;
        }

        if (empty($input->getArgument('username')) || empty($input->getArgument('token'))) {
            $helper = $this->getHelper('question');

            $question = new Question('Please enter your Discogs username: ', '');
            $username = $helper->ask($input, $output, $question);

            $question = new Question('Please enter your Discogs personal authentication token: ', '');
            $token = $helper->ask($input, $output, $question);
        } else {
            $username = $input->getArgument('username');
            $token = $input->getArgument('token');
        }

        $authentication->login($username, $token);

        $output->writeln("Authentication saved.");

        return Command::SUCCESS;
    }
}