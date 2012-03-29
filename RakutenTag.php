<?php
/**
 *
 *
 */
class RakutenTag
{
    public function __construct() {
        add_shortcode('rakuten', array($this, 'short_code'));
    }

    // エントリ内の [rakuten]search_word[/rakuten] を置換する。
    public function short_code($atts, $content = null) {
        return $this->search($content);
    }

    private function search($keyword, $limit = 1)
    {
        if ($output = self::fetchCache($keyword)) {
            return $output;
        }

        require_once dirname(__FILE__) . '/Rakuten.php';

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
        // @TODO implement me
        return false;

        $filename = dirname(__FILE__) . '/cache/';
        $filename .= str_replace(" ", "_", $keyword) . '.txt';

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

    private static function createCache($keyword, $output)
    {
        $filename = dirname(__FILE__) . '/cache/';
        $filename .= str_replace(" ", "_", $keyword) . '.txt';

        if (touch($filename)) {
            file_put_contents($filename, $output);
        }
    }

    public function is_mobile()
    {
        $ua = $_SERVER['HTTP_USER_AGENT'];

        if (preg_match("/^DoCoMo\//i", $ua)) {
            return true;
        } else if (preg_match("/^SoftBank/i", $ua)) { // SoftBank
            return true;
        } else if (preg_match("/^(Vodafone|MOT-)/i", $ua)) { // Vodafone 3G
            return true;
        } else if (preg_match("/^KDDI\-/i", $ua)) { // au (XHTML)
            return true;
        } else if (preg_match("/UP\.Browser/i", $ua)) { // au (HDML) TU-KA
            return true;
        } else if (preg_match("/WILLCOM/i", $ua)){ // WILLCOM Air EDGE
            return true;
        } else {
            return false;
        }
    }
}

