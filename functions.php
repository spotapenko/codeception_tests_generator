<?php


function processRecursivelyUrlsWithMaxLevel(&$siteTree, $url, $max_level, $current_level = 1, $parent_url = '', &$all_urls = array())
{

    if ($current_level > $max_level) {
        return;
    }

    $second_level_html = @file_get_contents(SITE_URL . $url);
    $second_level_urls = getUniqueUrlsFromHtml($second_level_html);

    $second_level_urls = array_diff($second_level_urls, $all_urls);
    $all_urls = array_merge($all_urls, $second_level_urls);

    if (!isset($siteTree[$url][$current_level]) && count($second_level_urls)) {
        $siteTree[$url][$current_level] = $second_level_urls;
    }

    foreach ($second_level_urls as $key => $val) {
        processRecursivelyUrlsWithMaxLevel($siteTree, $val, $max_level, $current_level + 1, $url, $all_urls);
    }
}

function isImageUrl($url)
{
    if (stripos($url, 'png')) {
        return true;
    }

    if (stripos($url, 'jpg')) {
        return true;
    }

    if (stripos($url, 'png')) {
        return true;
    }

    return false;
}

function getUniqueUrlsFromHtml($html)
{
    $allUrls = array_unique(getLinksFromHtml($html));

    foreach ($allUrls as $key => $url) {
        if (isImageUrl($url)) {
            unset($allUrls[$key]);
        }

        if (false !== stripos($url, '#')) {
            unset($allUrls[$key]);
        }

        $page_url = @file_get_contents(SITE_URL . $url);

        if ($page_url === false) {
            // print "$url is not exists!";
            unset($allUrls[$key]);
        }


        if (strpos(false == $page_url, 'html')) {
            //  print "$url is not contain html tag!";
            unset($allUrls[$key]);
        }
    }

    return $allUrls;
}

function getLinksFromHtml($html)
{
    $doc = new DOMDocument();
    $links = [];

    $opts = array(
        'output-xhtml' => true,
// Prevent DOMDocument from being confused about entities
        'numeric-entities' => true
    );

    @$doc->loadXML(tidy_repair_string($html, $opts));

    $xpath = new DOMXPath($doc);
// Tell $xpath about the XHTML namespace
    $xpath->registerNamespace('xhtml', 'http://www.w3.org/1999/xhtml');

    foreach ($xpath->query('//xhtml:a/@href') as $node) {
        $link = $node->nodeValue;

        $links[] = $link;
    }

    return $links;
}


function printValue($value)
{
    print "<pre>";
    print_r($value);
    print "</pre>";
}

function xPathSearchPatternValues($xpath, $pattern) {
    $links = [];
    foreach ($xpath->query($pattern) as $node) {
        //     printValue($node);
        $link = $node->nodeValue;
        //replace two space
        $link = str_replace('  ', ' ', $link);

        printValue($node);

        //html contains
//        if($link != strip_tags($link)) {
//           continue;
//        }

        //end of lines
        if(strstr($link, PHP_EOL)) {
            $link = str_replace(PHP_EOL, ' ', $link);
        }

        $links[] = $link;
    }

    return $links;
}

function getTestDataSeeText($all_urls) {

    $testData = [];

    foreach($all_urls as $url) {

//get heads
        $doc = new DOMDocument();
        $links = [];

        $opts = array(
            'output-xhtml' => true,
// Prevent DOMDocument from being confused about entities
            'numeric-entities' => true
        );

        $html =  @file_get_contents(SITE_URL . $url);

        @$doc->loadXML(tidy_repair_string($html, $opts));

        $xpath = new DOMXPath($doc);
// Tell $xpath about the XHTML namespace
        $xpath->registerNamespace('xhtml', 'http://www.w3.org/1999/xhtml');


      //  $result1 = xPathSearchPatternValues($xpath, "//xhtml:h1");
      //  $result2 = xPathSearchPatternValues($xpath, "//xhtml:h2");
        $result3 = xPathSearchPatternValues($xpath, "//xhtml:h3");
      //  $result4 = xPathSearchPatternValues($xpath, "//xhtml:li[contains(@class,'active')]");


        //$links = array_unique(array_merge($links,$result1));
        //$links = array_unique(array_merge($links,$result2));
        $links = array_unique(array_merge($links,$result3));
        //$links = array_unique(array_merge($links,$result4));

        $testData[$url] = $links;

        //   printValue($links);
    }



    return $testData;
}

function generateCodeceptionAcceptanceTests($testData)
{
    foreach($testData as $url => $texts) {
        $file = generateAcceptanceTestFileNameByUrl($url);

        $testFile = @file_get_contents($file);

        if ($testFile) {
            //create only new
            continue;
        }

        $testFile  = getAcceptanceTestOpenUrlContent($url);
        $testFile .= getAcceptanceTestSeeTextContent($texts);

        file_put_contents($file, $testFile);

        print "The file ".$file." has been created<br>\n";



    }
}

function generateAcceptanceTestFileNameByUrl($url) {
    $filePrefix = TEST_FOLDER;
    $fileSuffix = 'Cept.php';

    $fileName = str_replace('.html', '', $url);
    $fileName = str_replace('-', '_', $fileName);
    // underscored to upper-camelcase
    // e.g. "this_method_name" -> "ThisMethodName"
    //see http://php.net/manual/vote-note.php?id=92092&page=function.ucwords
    $fileName = preg_replace('/(?:^|_)(.?)/e',"strtoupper('$1')",$fileName);
    $file = $filePrefix . $fileName . $fileSuffix;

    return $file;
}

function getAcceptanceTestSeeTextContent($texts) {
    $testFile = '';
    foreach($texts as $replace_text) {
        $template = file_get_contents('templateAcceptanceTest.php');

        $variables = [
            '#TEXT' => $replace_text
        ];

        foreach($variables as $key => $value) {
            $testFile .= str_replace($key, $value, $template);
        }

        $testFile .= "\n\r";
    }

    return $testFile;
}

function getAcceptanceTestOpenUrlContent($url) {
    $template = file_get_contents('templateTest.php');

    $variables = [
        '#PAGE_URL' => $url
    ];

    foreach($variables as $key => $value) {
        $testFile = str_replace($key, $value, $template);
    }

    return $testFile;
}