<?php
/**
 *
 *
 */
require_once dirname(__FILE__) . '/Rakuten.php';

class RakutenTag
{
    public static function addShortCode()
    {
        add_shortcode('rakuten', array('RakutenTag', 'short_code'));
    }

    // エントリ内の [rakuten]search_word[/rakuten] を置換する。
    public static function short_code($atts, $content = null) {
        return self::search($content);
    }

    private static function search($keyword, $limit = 1)
    {
        if ($output = self::fetchCache($keyword)) {
            return $output;
        }

        if ($limit === 1) {
            $hits = '2';
        } else {
            $hits = $limit;
        }

        $options = get_option('wp_rakuten_options');

        // 楽天商品検索
        $api = Services_Rakuten::factory(
            'ItemSearch',
            $options['developer_id'],
            $options['affiliate_id']
        );

        $api->execute(
            array(
                'keyword' => $keyword,
                'availability' => '1',
                'sort' => '+affiliateRate',
                'hits' => $hits,
            )
        );

        $html = '';
        $data = $api->getResultData();

        // hitsが1だとitemそのものがきてしまうのでitemsにする
        if ($data['hits'] == 1) {
            $items = array($data['Items']['Item']);
        } else {
            $items = $data['Items']['Item'];
        }

        foreach ($items as $item) {
            if ((int)$item['imageFlag'] === 1) {
                $html .= "<p>";
                $html .= "<a href=\"{$item['affiliateUrl']}\">";
                $html .= "<img src=\"{$item['mediumImageUrl']}\"><br />";
                $html .= "{$item['itemName']}</a>";
                $html .= "</p>";
            }

            if ($html !== '' && $limit === 1) {
                break;
            }
        }

        $output  = '<!-- Rakuten Plugin Start -->';
        $output .= $html;
        $output .= '<!-- Rakuten Plugin End -->';

        self::createCache($keyword, $output);

        return $output;
    }

    private static function fetchCache($keyword)
    {
        $filename = self::createCachePath($keyword);

        if (!file_exists($filename)) {
            return false;
        }

        // キャッシュ作成日から24時間以上経過しているか
        if (time() <= filemtime($filename) + 86400) {
            return file_get_contents($filename);
        } else {
            return false;
        }
    }

    /**
     * @param string $keyword
     * @return string
     */
    private static function createCachePath($keyword)
    {
        $dir = dirname(dirname(__FILE__)) . '/cache/';

        $keyword = str_replace(" ", "_space_", $keyword);
        $keyword = str_replace("/", "_slash_", $keyword);

        return $dir . $keyword . '.txt';
    }

    private static function createCache($keyword, $output)
    {
        $filename = self::createCachePath($keyword);

        if (!touch($filename)) {
            throw new RuntimeException('cannot write file:' . $filename);
        }

        file_put_contents($filename, $output);
    }
}

