<?php

namespace app;

class Music
{
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

    public function getResourceMap($songId)
    {
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

        $vKey = $this->vKey();

        $fileList = [];

        foreach ($fileMap as $key => $item) {
            $url = sprintf($downloadUrl, $item[0], $songId, $item[1], $vKey, $item[1]);

            $headers = get_headers($url, 1);

            if (false !== strpos($headers[0], '200')) {
                $fileList[$key] = ['url' => $url, 'headers' => $headers, 'ext' => $item[1]];
            }
        }

        return $fileList;
    }

    public function download($name, $resourceMap, $singer, $album)
    {
        $fullPath = '/Volumes/Pronhub/Music/' . $singer;

        if (!is_dir($fullPath)) mkdir($fullPath);

        $fullPath .= '/' . $album . '/';

        if (!is_dir($fullPath)) mkdir($fullPath);

        $resource = end($resourceMap);

        $fileName = $name . $resource['ext'];

        if (file_exists($fullPath . $fileName)) {
            echo '歌曲已存在。';
            return -1;
        }

        $limit = 1024 * 1024;

        $handle = fopen($resource['url'], "r");

        while (!feof($handle)) {
            $contents = fread($handle, $limit);
            file_put_contents($fullPath . $fileName, $contents, FILE_APPEND | LOCK_EX);
        }

        fclose($handle);

        return true;
    }
}