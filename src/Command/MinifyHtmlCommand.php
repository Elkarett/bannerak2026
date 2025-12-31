<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'app:minify:html',
    description: 'Minifies extracted HTML files and adds boundary comments.',
)]
class MinifyHtmlCommand extends Command
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
        $finder->files()->in($targetDir)->name('*.html')->notName('*_min.html');

        foreach ($finder as $file) {
            $content = $file->getContents();

            // Remove existing boundary comments to avoid duplication
            $content = str_replace('<!-- Banner-aren kodearen hasiera -->', '', $content);
            $content = str_replace('<!-- Banner-aren kodearen amaiera -->', '', $content);

            // Remove all other HTML comments
            $content = preg_replace('/<!--.*?-->/s', '', $content);

            // Remove CSS comments
            $content = preg_replace('/\/\*.*?\*\//s', '', $content);

            // Simple minification: remove new lines and extra spaces
            $minified = preg_replace('/\s+/', ' ', $content);
            $minified = str_replace('> <', '><', $minified);
            $minified = trim($minified);

            // Add newlines between tags for readability (layers on separate lines)
            $minified = str_replace('><', ">\n<", $minified);

            // Add strategic newlines for style block (keep these for extra clarity)
            $minified = str_replace('<style>', "\n<style>", $minified);
            $minified = str_replace('</style>', "</style>\n", $minified);

            // Add boundary comments with newlines
            $finalContent = "<!-- Banner-aren kodearen hasiera -->\n" . $minified . "\n<!-- Banner-aren kodearen amaiera -->";

            $minFilename = str_replace('.html', '_min.html', $file->getFilename());
            file_put_contents($targetDir . '/' . $minFilename, $finalContent);

            $io->success("Minified and saved to AAFF/$minFilename");
        }

        return Command::SUCCESS;
    }
}
