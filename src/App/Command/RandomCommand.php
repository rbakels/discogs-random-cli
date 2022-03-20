<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Exception\AuthenticationMissingException;

use App\Lib\Discogs;
use App\Lib\Application;

error_reporting(E_ALL ^ E_WARNING ^ E_DEPRECATED); 

class RandomCommand extends Command
{
    protected static $defaultName = 'random';

    protected function configure(): void
    {
        $this
            ->setDescription('Generate a random release')
            ->setHelp('Generate a random release from your personal collection to listen next.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $application = new Application($output);

            $releases = $application->getAllReleases();
            $suggestedReleases = $application->getAllSuggestedReleases();

            // Unset suggested releases from releases array, so we don't suggest a release twice.
            $output->writeln("Found previous suggested releases: [" . join(",", $suggestedReleases) . "]", OutputInterface::VERBOSITY_DEBUG);

            foreach ($suggestedReleases as $suggestedRelease) {
                if (array_key_exists($suggestedRelease, $releases)) {
                    $output->writeln("Unsetting suggested release with id = " . $suggestedRelease, OutputInterface::VERBOSITY_DEBUG);

                    unset($releases[$suggestedRelease]);
                }
            }

            if (empty($releases)) {
                $output->writeln("No more releases to choose from.");
            } else {
                $releaseId = array_rand($releases);
                $application->registerSuggestedReleases($releaseId);
                
                $release = $application->getBasicInformationOfRelease($releaseId);
                $this->displayRelease($output, $release);
            }

            return Command::SUCCESS;
        } catch (AuthenticationMissingException $e) {
            $output->writeln("Authenticate first by using the login command.");
            
            return Command::SUCCESS;
        }
    }

    private function displayRelease(OutputInterface $output, $release)
    {
        $output->writeln("<fg=#a6cdd9>" . $release['artist'] . " - " . $release['title'] . "</>");
        $output->writeln("");

        if ($this->isItermCapable()) {
            $itermInlineImage = chr(27) .
                ']1337;File=inline=1:'
                . base64_encode(file_get_contents($release['images']['local']['cover']))
                . chr(7);

            $output->writeln($itermInlineImage);
        } else {
            $output->writeln("<image cannot be displayed>");
            $output->writeln("Url: " . $release['images']['local']['cover']);
        }

        $output->writeln("");
        $output->writeln("<fg=#d9c7a6>Metadata</>:");
        $output->writeln("<fg=#aed9a6>Title</>: " . $release['title']);
        $output->writeln("<fg=#aed9a6>Artist</>: " . $release['artist']);
        $output->writeln("<fg=#aed9a6>Released</>: " . $release['released']);
        $output->writeln("<fg=#aed9a6>Genre</>: " . $release['genres']);
    }

    private function isItermCapable()
    {
        return version_compare((string) $this->guessVersion(), '3.0.0', '>=');
    }

    private function guessVersion()
    {
        if ($version = getenv('TERM_PROGRAM_VERSION')) {
            return $version;
        } else if (is_file('/Applications/iTerm.app/Contents/Info.plist')) {
            $cmd = Command::create('cat')
                ->withArgument('/Applications/iTerm.app/Contents/Info.plist')
                ->pipe(
                    Command::create('grep')
                       ->withArgument('CFBundleVersion')
                       ->withShortOption('A1')
                );
            $shell = new Exec();
            $out = $shell->exec($cmd);
            $xml = simplexml_load_string('<root>' . join('', $out) . '</root>');
            return (string) $xml->string;
        }

        return '';
    }
}