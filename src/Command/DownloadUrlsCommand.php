<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:download-urls',
    description: 'Downloads HTML content from URLs listed in banners.txt',
)]
class DownloadUrlsCommand extends Command
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
        $bannersFile = $this->projectDir . '/banners.txt';
        $downloadDir = $this->projectDir . '/var/downloads';

        if (!file_exists($bannersFile)) {
            $io->error('banners.txt file not found in project root.');
            return Command::FAILURE;
        }

        if (!file_exists($downloadDir)) {
            mkdir($downloadDir, 0777, true);
        }

        $urls = file($bannersFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($urls as $url) {
            $io->text("Downloading: $url");
            try {
                $response = $this->httpClient->request('GET', $url);
                $content = $response->getContent();

                // Extract filename from URL or generate one
                $filename = basename(parse_url($url, PHP_URL_PATH));
                if (empty($filename)) {
                    $filename = 'downloaded_' . md5($url) . '.html';
                }

                file_put_contents($downloadDir . '/' . $filename, $content);
                $io->success("Saved to $downloadDir/$filename");
            } catch (\Exception $e) {
                $io->error("Failed to download $url: " . $e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}
