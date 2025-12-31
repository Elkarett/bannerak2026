<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
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
        $sourceDir = $this->projectDir . '/Banner2026';
        $destDir = $this->projectDir . '/AAFF';

        if (!is_dir($sourceDir)) {
            $io->error("Source directory $sourceDir does not exist.");
            return Command::FAILURE;
        }

        if (!is_dir($destDir)) {
            mkdir($destDir, 0777, true);
        }

        $finder = new Finder();
        $finder->files()->in($sourceDir)->name('*.html');

        foreach ($finder as $file) {
            $content = $file->getContents();
            $startTag = '<!-- Banner-aren kodearen hasiera -->';
            $endTag = '<!-- Banner-aren kodearen amaiera -->';

            $startPos = strpos($content, $startTag);
            $endPos = strpos($content, $endTag);

            if ($startPos !== false && $endPos !== false) {
                $length = ($endPos + strlen($endTag)) - $startPos;
                $extracted = substr($content, $startPos, $length);

                $dateSuffix = date('Ymd');
                $originalFilename = $file->getFilename();
                $extension = $file->getExtension();
                $basename = pathinfo($originalFilename, PATHINFO_FILENAME);

                $newFilename = $basename . '-' . $dateSuffix . '.' . $extension;
                $outputFile = $destDir . '/' . $newFilename;

                file_put_contents($outputFile, $extracted);

                $io->success("Extracted banner to AAFF/{$newFilename}");
            } else {
                $io->warning("Banner tags not found in {$file->getFilename()}");
            }
        }

        return Command::SUCCESS;
    }
}
