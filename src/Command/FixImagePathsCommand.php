<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'app:fix:image-paths',
    description: 'Fixes image paths in extracted HTML files.',
)]
class FixImagePathsCommand extends Command
{
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $targetDir = $this->projectDir . '/AAFF';

        if (!is_dir($targetDir)) {
            $io->error("Target directory $targetDir does not exist.");
            return Command::FAILURE;
        }

        $finder = new Finder();
        $finder->files()->in($targetDir)->name('*.html');

        foreach ($finder as $file) {
            $content = $file->getContents();

            // Ensure ero_banner/img/ starts with a slash
            // Replace any occurrence of "ero_banner/img/" that doesn't start with "/" with "/ero_banner/img/"
            $updatedContent = preg_replace('/(?<!\/)ero_banner\/img\//', '/ero_banner/img/', $content);

            if ($content !== $updatedContent) {
                file_put_contents($file->getPathname(), $updatedContent);
                $io->success("Fixed image paths in {$file->getFilename()}");
            } else {
                $io->info("No image path fixes needed for {$file->getFilename()}");
            }
        }

        return Command::SUCCESS;
    }
}
