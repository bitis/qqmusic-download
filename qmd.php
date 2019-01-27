<?php
require __DIR__ . '/vendor/autoload.php';

use App\Music;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

(new Application('QQ Music Download', '1.0.0'))
    ->register('qmd')
    ->addArgument('albumLink', InputArgument::REQUIRED, '歌曲名称')
    ->setCode(function (InputInterface $input, OutputInterface $output) {

        $albumLink = $input->getArgument('albumLink');

        $html = file_get_contents($albumLink);
        $crawler = new Crawler($html);

        $music = new Music();

        $album = trim($crawler->filter('.data__name_txt')->text());
        $singer = trim($crawler->filter('.data__singer')->text());
        $crawler->filter('.songlist__songname_txt')->each(
            function (Crawler $item) use ($album, $singer, $music) {
                $name = trim($item->text());
                $link = $item->children()->attr('href');
                $songId = substr(basename($link), 0, -5);

                echo $singer, "\t", $album, "\t", $name, "\t", $songId, "\t";

                if ($resourceMap = $music->getResourceMap($songId)) {
                    $music->download($name, $resourceMap, $singer, $album);
                }
                echo "Done.\n";
            });

        return 0;
    })
    ->getApplication()
    ->setDefaultCommand('qmd', true)
    ->run();