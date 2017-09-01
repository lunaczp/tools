<?php
$domain = "http://imysql.com/mysql-internal-manual/";
$index = "index.html";

$dir = "/tmp/mysql-internal-manual";

if (!file_exists($dir)) {
    mkdir($dir) || exit("mkdir fail");
}

$doneFile = "/tmp/tmpDone.txt";
$done = initDone($doneFile);

$i = 0;
$todo = [$index];
while (!empty($todo)) {
    $url = array_shift($todo);
    $content = get($url);
    setDone($url);
    saveContent($url, $content);
    
    if (strpos($url, ".html") !== false) {
        $urls = parseContent($content);
        foreach ($urls as $new) {
            if (!isDone($new) && !in_array($new, $todo)) {
                array_push($todo, $new);
            }
        }
    }
    echo $i++."\t";
}

function get($url) {
    global $domain;
    $url = $domain .$url;
    return Curl::getUrl($url);
}

function parseContent($content) {
    $ret = [];
    if (preg_match_all("#href=\"([a-zA-Z0-9-_]+\.html)\"#", $content, $m)) {
        $ret = array_unique($m[1]);
    }

    if (preg_match_all("/(src|href)=\"([0-9a-zA-Z_\-\/]+\.(css|js|png|jpg|jpeg)(\?v=\d+)?)\"/", $content, $m)) {
        $ret = array_merge($ret, array_unique($m[2]));
    }

    return $ret;
}

function saveContent($url, $content) {
    global $dir;
    if (strpos($url, "/") !== false) {
        $last_pos = strrpos($url, "/");
        $name = substr($url, $last_pos + 1);
        $dir_prefix = substr($url, 0 ,$last_pos);
        $dirFull = $dir ."/" .$dir_prefix;
        
        if (!file_exists($dirFull)) {
            mkdir($dirFull, 0777, true);
        }
    } else {
        $dirFull = $dir;
        $name = $url;
    }
    
    file_put_contents($dirFull ."/" .$name, $content);
}

function initDone($file) {
    if (file_exists($file)) {
        return file($file, FILE_IGNORE_NEW_LINES);
    }
    return [];
}

function isDone($url) {
    global $done;
    return in_array($url, $done);
    
}

function setDone($url) {
    global $doneFile;
    global $done;
    file_put_contents($doneFile, $url.PHP_EOL, FILE_APPEND);
    array_push($done, $url);
}


class Curl {

    public static function getUrl($url) {
        if (empty($url)) {
            return '';
        }

        $maxTry = 3;
        while ($maxTry--) {
            $ch = curl_init();
            $header = self::genHeader();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);

            $data = curl_exec($ch);

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($httpCode == 200) {
                return $data;
            }
        }
        return '';
    }

    private static function genHeader($data = array()) {
        $header = array();
        $header[] = 'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8';
        $header[] = 'Accept-Language:zh-CN,zh;q=0.8,en;q=0.6';
        $header[] = 'Cache-Control: no-cache';
        $header[] = 'Connection:keep-alive';
        $header[] = 'User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.86 Safari/537.36';

        if(!empty($data['header'])) {
            $header = array_merge($header, $data['header']);
        }
        return $header;
    }
}
