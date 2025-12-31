<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'app:download:images',
    description: 'Downloads images found in the extracted HTML files.',
)]
class DownloadImagesCommand extends Command
{
    private string $projectDir;
    private HttpClientInterface $httpClient;

    public function __construct(string $projectDir, HttpClientInterface $httpClient)
    {
        $this->projectDir = $projectDir;
        $this->httpClient = $httpClient;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $targetDir = $this->projectDir . '/AAFF';
        $sourceDir = $this->projectDir . '/Banner2026';
        $imgDestDir = $targetDir . '/ero_banner/img';

        if (!file_exists($imgDestDir)) {
            mkdir($imgDestDir, 0777, true);
        }

        // Pre-scan source directory for all images to create a map [filename => path]
        $io->text("Scanning source directory for images...");
        $sourceFinder = new Finder();
        $sourceFinder->files()->in($sourceDir)->name('/\.(png|jpg|jpeg|gif|webp|svg)$/i');

        $imageMap = [];
        foreach ($sourceFinder as $imgFile) {
            $imageMap[$imgFile->getFilename()] = $imgFile->getPathname();
        }

        $io->success(sprintf("Found %d images in source directory.", count($imageMap)));

        $finder = new Finder();
        $finder->files()->in($targetDir)->name('*.html')->notName('*_min.html');

        foreach ($finder as $file) {
            $content = $file->getContents();

            // Find all image sources
            $imagePaths = [];
            if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/', $content, $matches)) {
                $imagePaths = array_merge($imagePaths, $matches[1]);
            }
            if (preg_match_all('/url\((?:["\']?)([^"\')]+)(?:["\']?)\)/', $content, $matches)) {
                $imagePaths = array_merge($imagePaths, $matches[1]);
            }
            $imagePaths = array_unique($imagePaths);

            if (empty($imagePaths)) {
                continue;
            }

            foreach ($imagePaths as $imgSrc) {
                $filename = basename($imgSrc);

                if (isset($imageMap[$filename])) {
                    $sourcePath = $imageMap[$filename];
                    $destPath = $imgDestDir . '/' . $filename;

                    if (!file_exists($destPath)) {
                        copy($sourcePath, $destPath);
                        $io->text("Copied $filename");
                    }
                } else {
                    $io->warning("Image $filename referenced in {$file->getFilename()} not found in source directory.");
                }
            }
        }

        $io->success("Images copied to $imgDestDir");

        return Command::SUCCESS;
    }
}
