<?php
require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

(new Application('QQ Music Download', '1.0.0'))
    ->register('qmd')
    ->addArgument('name', InputArgument::REQUIRED, '歌曲名称')
    ->setCode(function (InputInterface $input, OutputInterface $output) {

        $io = new Symfony\Component\Console\Style\SymfonyStyle($input, $output);

        $name = $input->getArgument('name');

        $io->text($name);

        function search($name)
        {
            $url = 'http://s.music.qq.com/fcgi-bin/music_search_new_platform?t=0&n=1&aggr=1&cr=1&loginUin=0&format=json&inCharset=GB2312&outCharset=utf-8&notice=0&platform=jqminiframe.json&needNewCode=0&p=1&catZhida=0&w=' . urlencode($name);

            $content = file_get_contents($url);

            $result = json_decode($content);

            return $result->data->song->list[0];
        }

        function vKey()
        {
            $opts = stream_context_create(['http' =>
                [
                    'method' => 'GET',
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                        "User-Agent: Mozilla/4.0 (compatible; MSIE 9.0; Windows NT 6.1)\r\n" .
                        "Authorization: Bearer YWRtaW46YWRtaW4=\r\n"
                ]
            ]);

            $url = 'http://base.music.qq.com/fcgi-bin/fcg_musicexpress.fcg?json=3&format=json&guid=2057046240';

            $result = json_decode(file_get_contents($url, false, $opts));

            return $result->key;
        }

        $song = search($name);

        $io->newLine();
        $io->text([
            '歌曲：' . $song->fsong,
            '专辑：' . $song->albumName_hilight,
            '歌手：' . $song->fsinger,
        ]);

        $songID = explode('|', $song->f)[20];

        $fileMap = [
            'm4a' => ['C400', '.m4a'],
            '128(mp3)' => ['M500', '.mp3'],
            '192(ogg)' => ['O600', '.ogg'],
            '192(acc)' => ['C600', '.acc'],
            '320(mp3)' => ['M800', '.mp3'],
            'flac' => ['F000', '.flac'],
            'ape' => ['F000', '.ape'],
        ];

        $downloadUrl = 'http://dl.stream.qqmusic.qq.com/%s%s%s?guid=2057046240&vkey=%s&uin=0&fromtag=91&%s';

        $vKey = vKey();

        $fileList = [];

        foreach ($fileMap as $key => $item) {
            $url = sprintf($downloadUrl, $item[0], $songID, $item[1], $vKey, $item[1]);

            $headers = get_headers($url, 1);

            if (false !== strpos($headers[0], '200')) {
                $fileList[$key] = ['url' => $url, 'headers' => $headers];
            }
        }

        if (empty($fileList)) {
            $io->error('未能找到有效资源。');
            return -1;
        } else {
            $choice = $io->choice('选择要下载的文件', array_keys($fileList));

            $fileSize = $fileList[$choice]['headers']['Content-Length'];

            $path = './';

            $fileName = $song->fsong . $fileMap[$choice][1];

            $io->text($fileName);

            if (file_exists($path.$fileName)) {
                $io->warning('歌曲已存在。');
                return -1;
            }

            $io->progressStart($fileSize);

            $limit = 1024 * 1024;

            $handle = fopen($fileList[$choice]['url'], "r");

            while (!feof($handle)) {
                $contents = fread($handle, $limit);
                file_put_contents('./' . $fileName, $contents, FILE_APPEND | LOCK_EX);

                $io->progressAdvance(strlen($contents));
            }

            fclose($handle);

            $io->progressFinish();
        }

        return 0;
    })
    ->getApplication()
    ->setDefaultCommand('qmd', true)
    ->run();