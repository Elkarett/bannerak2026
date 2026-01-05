<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'app:extract:banner',
    description: 'Extracts banner content from downloaded HTML files.',
)]
class ExtractBannerCommand extends Command
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
        $helper = $this->getHelper('question');
        $sourceDir = $this->projectDir . '/Banner2026';
        $destDir = $this->projectDir . '/AAFF';

        if (!is_dir($sourceDir)) {
            $io->error("Source directory $sourceDir does not exist.");
            return Command::FAILURE;
        }

        if (!is_dir($destDir)) {
            mkdir($destDir, 0777, true);
        }

        // 1. Get subdirectories
        $finderDirs = new Finder();
        $finderDirs->directories()->in($sourceDir)->depth(0);

        $dirs = [];
        foreach ($finderDirs as $dir) {
            $dirs[] = $dir->getBasename();
        }

        if (empty($dirs)) {
            $io->error("No subdirectories found in $sourceDir");
            return Command::FAILURE;
        }

        $questionDir = new ChoiceQuestion(
            'Please select the folder to process:',
            $dirs
        );
        $questionDir->setErrorMessage('Directory %s is invalid.');

        $selectedDir = $helper->ask($input, $output, $questionDir);
        $io->info('You selected: ' . $selectedDir);

        $workingDir = $sourceDir . '/' . $selectedDir;

        // 2. Get files in selected directory
        $finderFiles = new Finder();
        $finderFiles->files()->in($workingDir)->name('*.html')->depth(0);

        $files = [];
        foreach ($finderFiles as $file) {
            $files[] = $file->getFilename();
        }

        if (empty($files)) {
            $io->error("No HTML files found in $workingDir");
            return Command::FAILURE;
        }

        $filesOptions = array_merge(['All'], $files);

        $questionFile = new ChoiceQuestion(
            'Please select the file to process (default: All):',
            $filesOptions,
            0 // Default to 'All'
        );
        $questionFile->setMultiselect(true);
        $questionFile->setErrorMessage('File %s is invalid.');

        $selectedFiles = $helper->ask($input, $output, $questionFile);

        if (in_array('All', $selectedFiles)) {
            $selectedFiles = $files;
        }

        $io->text('Processing files: ' . implode(', ', $selectedFiles));

        // Process selected files
        foreach ($selectedFiles as $filename) {
            $filePath = $workingDir . '/' . $filename;
            if (!file_exists($filePath)) {
                $io->warning("File not found: $filePath");
                continue;
            }

            $content = file_get_contents($filePath);
            $startTag = '<!-- Banner-aren kodearen hasiera -->';
            $endTag = '<!-- Banner-aren kodearen amaiera -->';

            $startPos = strpos($content, $startTag);
            $endPos = strpos($content, $endTag);

            if ($startPos !== false && $endPos !== false) {
                $length = ($endPos + strlen($endTag)) - $startPos;
                $extracted = substr($content, $startPos, $length);

                $dateSuffix = date('Ymd');
                $basename = pathinfo($filename, PATHINFO_FILENAME);
                $extension = pathinfo($filename, PATHINFO_EXTENSION);

                $newFilename = $basename . '-' . $dateSuffix . '.' . $extension;
                // Assuming we want to keep them in AAFF root or maybe AAFF/Subdir? 
                // The original code put them in AAFF root.
                $outputFile = $destDir . '/' . $newFilename;

                file_put_contents($outputFile, $extracted);

                $io->success("Extracted banner to AAFF/{$newFilename}");
            } else {
                $io->warning("Banner tags not found in {$filename}");
            }
        }

        return Command::SUCCESS;
    }
}
