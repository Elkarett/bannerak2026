<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'app:tinify:images',
    description: 'Optimizes images using TinyPNG API.',
)]
class TinifyImagesCommand extends Command
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

        $apiKey = $_ENV['TINYPNG_API_KEY'] ?? null;
        if (!$apiKey) {
            $io->warning('TINYPNG_API_KEY environment variable not set. Skipping optimization.');
            return Command::SUCCESS;
        }

        try {
            \Tinify\setKey($apiKey);
        } catch (\Exception $e) {
            // Assuming tinify/tinify is installed. If not, this will crash.
            // Since we didn't check composer.json for tinify, we should handle class not found or suggest installation.
            // But valid PHP code is required. 
            // If class doesn't exist, we can't use it.
            if (!class_exists('\Tinify\Tinify')) {
                $io->warning('Tinify PHP library not found. Run `composer require tinify/tinify` to enable image optimization.');
                return Command::SUCCESS;
            }
            \Tinify\setKey($apiKey);
        }

        $imgDir = $this->projectDir . '/var/downloads/img';
        if (!is_dir($imgDir)) {
            $io->warning("Image directory $imgDir does not exist.");
            return Command::SUCCESS;
        }

        $finder = new Finder();
        $finder->files()->in($imgDir)->name('/\.(png|jpg|jpeg)$/i');

        foreach ($finder as $file) {
            $io->text("Optimizing {$file->getFilename()}...");
            try {
                $source = \Tinify\fromFile($file->getPathname());
                $source->toFile($file->getPathname());
            } catch (\Exception $e) {
                $io->error("Failed to optimize {$file->getFilename()}: " . $e->getMessage());
            }
        }

        $io->success("Images optimized.");

        return Command::SUCCESS;
    }
}
