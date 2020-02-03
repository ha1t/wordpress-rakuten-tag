<?php
require_once dirname(__FILE__) . '/vendor/rakuten-ws/rws-php-sdk/autoload.php';

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

    private static function search(string $keyword)
    {
        if ($output = get_transient($keyword)) {
            return $output;
        }

        $options = get_option('wp_rakuten_options');

        // 楽天商品検索
        $client = new RakutenRws_Client();
        $client->setApplicationId($options['developer_id']);
        $client->setAffiliateId($options['affiliate_id']);

        $response = $client->execute(
            'IchibaItemSearch',
            array(
                'keyword' => $keyword,
                'availability' => '1',
                'sort' => '+affiliateRate',
                'hits' => 1,
            )
        );

        $html = '';

        if ($response->isOk()) {
            foreach ($response as $item) {
                if ((int)$item['imageFlag'] === 1) {
                    $html .= "<p>";
                    $html .= "<a href=\"{$item['affiliateUrl']}\">";
                    $html .= "<img src=\"{$item['mediumImageUrls'][0]['imageUrl']}\"><br />";
                    $html .= "{$item['itemName']}</a>";
                    $html .= "</p>";
                }
            }
        }

        $output  = '<!-- Rakuten Plugin Start -->';
        $output .= $html;
        $output .= '<!-- Rakuten Plugin End -->';

        set_transient($keyword, $output, WEEK_IN_SECONDS + rand(0, WEEK_IN_SECONDS));

        return $output;
    }

}

