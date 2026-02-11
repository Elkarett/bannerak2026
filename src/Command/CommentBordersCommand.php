<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'app:fix:banners:comment-borders',
    description: 'Comments out debug borders with 0.05 opacity in AAFF files.',
)]
class CommentBordersCommand extends Command
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

        $count = 0;
        foreach ($finder as $file) {
            $content = $file->getContents();
            $updatedContent = $content;

            // Regex to match the specific borders with 0.05 opacity
            // Matches: border: 1px solid rgba(R, G, B, 0.05);
            // It ensures it's not already commented out by checking it doesn't start with "/* " (simplified check)

            $pattern = '/(?<!\/\*\s)(border:\s*1px\s*solid\s*rgba\(\d+,\s*\d+,\s*\d+,\s*0\.05\);)/';
            $replacement = '/* $1 */';

            $updatedContent = preg_replace($pattern, $replacement, $updatedContent);

            if ($content !== $updatedContent) {
                file_put_contents($file->getPathname(), $updatedContent);
                $io->writeln("Commented borders in <info>{$file->getFilename()}</info>");
                $count++;
            }
        }

        $io->success("Finished! Modified $count files.");

        return Command::SUCCESS;
    }
}
