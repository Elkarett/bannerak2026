<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use ZipArchive;

#[AsCommand(
    name: 'app:zip:downloads',
    description: 'Zips the minified HTML and images into a downloadable archive, then cleans up.',
)]
class ZipDownloadsCommand extends Command
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
        $zipsDir = $targetDir . '/zips';

        if (!is_dir($zipsDir)) {
            mkdir($zipsDir, 0777, true);
        }

        $finder = new Finder();
        $finder->files()->in($targetDir)->name('*_min.html');

        // Group files by base name (removing date and language)
        $groups = [];
        $languages = ['-es', '-eu', '-ca', '-gl',];

        foreach ($finder as $file) {
            $filename = $file->getFilename();
            // Remove date suffix: -YYYYMMDD_min.html
            $base = preg_replace('/-\d{8}_min\.html$/', '', $filename);

            // Remove language suffix
            foreach ($languages as $lang) {
                if (str_ends_with($base, $lang)) {
                    $base = substr($base, 0, -strlen($lang));
                    break;
                }
            }

            $groups[$base][] = $file;
        }

        foreach ($groups as $baseName => $files) {
            $zipFilename = $zipsDir . '/' . $baseName . '.zip';
            $zip = new ZipArchive();

            if ($zip->open($zipFilename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                $io->error("Cannot open <$zipFilename>");
                continue;
            }

            $addedImages = [];

            foreach ($files as $file) {
                // Add the HTML file at root
                $zip->addFile($file->getPathname(), $file->getFilename());

                // Find used images
                $content = $file->getContents();
                $imagePaths = [];

                // Match img src
                if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/', $content, $matches)) {
                    $imagePaths = array_merge($imagePaths, $matches[1]);
                }

                // Match css url()
                if (preg_match_all('/url\((?:["\']?)([^"\')]+)(?:["\']?)\)/', $content, $matches)) {
                    $imagePaths = array_merge($imagePaths, $matches[1]);
                }

                $imagePaths = array_unique($imagePaths);

                foreach ($imagePaths as $imgSrc) {
                    $relativePathOnDisk = ltrim($imgSrc, '/');
                    $absolutePath = $targetDir . '/' . $relativePathOnDisk;

                    // Support for images that might be in the root of the extract folder (fallback)
                    if (!file_exists($absolutePath)) {
                        $absolutePath = $targetDir . '/' . basename($imgSrc);
                    }

                    if (file_exists($absolutePath)) {
                        // All images go into ero_banner/img/ in the ZIP
                        $zipPath = 'ero_banner/img/' . basename($imgSrc);

                        if (!isset($addedImages[$zipPath])) {
                            if ($zip->addFile($absolutePath, $zipPath)) {
                                //$io->text("Added $zipPath to zip");
                            } else {
                                $io->error("Failed to add $zipPath to zip");
                            }
                            $addedImages[$zipPath] = true;
                        }
                    } else {
                        $io->warning("File not found for zip: $absolutePath");
                    }
                }
            }

            $zip->close();
            $io->success("Created Grouped ZIP: " . basename($zipFilename) . " with " . count($files) . " files.");
        }

        // Cleanup
        $io->section('Cleaning up...');
        $filesystem = new Filesystem();

        // 1. Delete all HTML files in AAFF
        $finderCleanup = new Finder();
        // Looking for all .html files, not just _min, as requested ("html-ak")
        $finderCleanup->files()->in($targetDir)->name('*.html')->depth(0);

        if ($finderCleanup->hasResults()) {
            foreach ($finderCleanup as $file) {
                $filesystem->remove($file->getPathname());
                $io->text('Deleted: ' . $file->getFilename());
            }
        }

        // 2. Delete ero_banner directory
        $eroBannerDir = $targetDir . '/ero_banner';
        if ($filesystem->exists($eroBannerDir)) {
            $filesystem->remove($eroBannerDir);
            $io->text('Deleted directory: ero_banner');
        }

        $io->success('Cleanup completed.');

        return Command::SUCCESS;
    }
}
