<?php
$expected = "";
$r = [];

$url = [
    'paraphrase' => 'https://paraphrase.tools/php/process.php',
    'translate'  => 'https://smodin.me/smodin-service/translate'
];

function paraphrase($text, $id)
{
    global $url;
    $context = "";
    $images  = [];
    $i       = 1;

    $dom = new DOMDocument;
    @$dom->loadHTML('<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"></head><body><div id="unique">' .  $text . '</div></body>');
    $div  = $dom->getElementById('unique');

    foreach ($div->childNodes as $paraf) {
        if (isset($paraf->tagName) && $paraf->tagName == "p") {
            $context .= "<p>" . "{$paraf->nodeValue}" . "</p>";
        } else if (isset($paraf->tagName) && $paraf->tagName == "h2") {
            $context .= "<h2>" . "{$paraf->nodeValue}" . "</h2>";
        } else if (isset($paraf->tagName) && $paraf->tagName == "blockquote") {
            $context .= "<blockquote>" . "{$paraf->nodeValue}" . "</blockquote>";
        } else if (isset($paraf->tagName) && $paraf->tagName == "img") {
            // paraphrase aracı görselin src'sini değiştirmesin diye anlamsız hale getiriyorum.
            $images[$i]['src'] = $paraf->getAttribute('src');
            $images[$i]['numb'] = $i;
            $context .=  "<p>" . "WI" . $i . "</p>";
            $i++;
        } else if (isset($paraf->tagName) && $paraf->tagName == "strong") {
            $context .= "<strong>" . "{$paraf->nodeValue}" . "</strong>";
        } else {
            $context .= $paraf->wholeText;
        }
    }

    $data = $headers = [];
    $data['data'] = $context;
    $data['lang'] = 'en';

    $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8';

    $spinContent = urldecode(curl($url['paraphrase'], $data, $headers));

    return paraphraseToTranslate($spinContent, $images, "", $id);
}

function translate($sub)
{
    global $url;

    $data = $headers = [];
    $data['from'] = 'en';
    $data['text'] = $sub;
    $data['to'] = ["tr"];
    $data['identifier'] = 'tVVuLrrrtQua';

    $headers[] = 'Content-Type: application/json';
    // identifier: "ruutbQQSVufu"

    $result = curl($url['translate'], $data, $headers, true);
    $before = ["< ", " <", " < ", " >", "> ", " > ", " / ", " /", "/ ", ""];
    $after = ["<", "<", "<", ">", ">", ">", "/", "/", "/", "dolar"];

    return str_replace($before, $after, $result[0]["text"]);
}


function paraphraseToTranslate($text, $images, $array, $id)
{
    global $expected;
    global $r;
    $context = "";
    $dom = new DOMDocument;
    @$dom->loadHTML('<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"></head><body><div id="parse">' .  $text . '</div></body>');
    $div  = $dom->getElementById('parse');

    $r[] = $id;

    if (!in_array($id, $r)) {
        $expected = "";
    }

    foreach ($div->childNodes as $paraf) {
        if (isset($paraf->tagName) && $paraf->tagName == "p") {
            if ((mb_strlen($context, "UTF-8") + mb_strlen($paraf->nodeValue, "UTF-8") + 7) <= 1000) {
                $context .= "<p>" . "{$paraf->nodeValue}" . "</p>";
            } else {
                break;
            }
        } else if (isset($paraf->tagName) && $paraf->tagName == "h2") {
            if ((mb_strlen($context, "UTF-8") + mb_strlen($paraf->nodeValue, "UTF-8") + 9) <= 1000) {
                $context .= "<h2>" . "{$paraf->nodeValue}" . "</h2>";
            } else {
                break;
            }
        } else if (isset($paraf->tagName) && $paraf->tagName == "blockquote") {
            if ((mb_strlen($context, "UTF-8") + mb_strlen($paraf->nodeValue, "UTF-8") + 25) <= 1000) {
                $context .= "<blockquote>" . "{$paraf->nodeValue}" . "</blockquote>";
            } else {
                break;
            }
        } else if (isset($paraf->tagName) && $paraf->tagName == "strong") {
            if ((mb_strlen($context, "UTF-8") + mb_strlen($paraf->nodeValue, "UTF-8") + 17) <= 1000) {
                $context .= "<strong>" . "{$paraf->nodeValue}" . "</strong>";
            } else {
                break;
            }
        } else {
            $context .= $paraf->wholeText;
        }
    }

    // paraphrase aracı görselin src'sini değiştirmesin diye anlamsız hale getirdiğim yerleri düzeltiyorum.
    if (!empty($context)) {
        $lastStr = translate($context);
        if (!empty($images)) {
            foreach ($images as $image) {
                $before[] = "<p>" . "WI" . $image['numb'] . "</p>"; //sıra
                $after[] = '<img src="' . $image['src'] . '" />'; //src
            }
            $lastStr = str_replace($before, $after, $lastStr);
            $array .= $lastStr;
            $azalt = str_replace($context, "", $text);
        } else {
            $array .= $lastStr;
            $azalt = str_replace($context, "", $text);
        }
        $expected .= $array;
        paraphraseToTranslate($azalt, $images, $array, $id);
    } else {
        $expected = $array;
    }
    return $expected;
}


function curl($url, $data = [], $headers = [], $decode = true)
{
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    $curl_response = curl_exec($curl);
    curl_close($curl);

    if ($decode) {
        return json_decode($curl_response);
    }

    return $curl_response;
}
