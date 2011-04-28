<?php
/**
Plugin Name: WP Rakuten Tag
Plugin URI: http://blog.newf.jp/myplugin/wp-rakuten-link/
Description: [rakuten][/rakuten]で囲まれたwordから楽天市場の個別商品を検索しエントリー上に表示します。利用にあたっては、楽天ウェブサービスのデベロッパーIDが別途必要です。WP Rakuten Linkを参考にさせていただきました。
Author: halt
Version: 0.0.2
Author URI: http://project-p.jp/halt/

[更新履歴]
2012/04/27 0.0.2 : githubに公開

@todo モバイルの時はモバイルのページに飛ばすようにする
@todo 24h キャッシュをつける
 */

mb_internal_encoding("UTF-8");
date_default_timezone_set('Asia/Tokyo');

$RakutenTag = new RakutenTag();
class RakutenTag
{
    public function __construct() {
        add_shortcode('rakuten', array($this, 'short_code'));
    }

    // エントリ内の [rakuten]search_word[/rakuten] を置換する。
    public function short_code($atts, $content = null) {
        $classcode = str_replace(":", "", $content);

        if ( $this->is_mobile() ){
            $rakutencode = '<div class="rakuten_m_details">';
            $rakutencode .= $this->get_data($content) . '</div>';
        } else {
            $rakutencode = '<div class="rakuten_details">' . $this->get_data(htmlspecialchars($content)) . '</div>';
        }

        return $this->search($content);
        return $rakutencode;

    }

    private function search($keyword, $limit = 1)
    {
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

        if ($this->use_cache === true) {
            // @TODO implement me
        }

        $output  = '<!-- Rakuten Plugin Start -->';
        $output .= $html;
        $output .= '<!-- Rakuten Plugin End -->';
        return $output;
    }

    public function check_cache($itemcode)
    {
        // @TODO implement me
        return false;

        $filename = dirname(__FILE__) . '/cache/' . str_replace(":", "_", htmlspecialchars($itemcode)) . '.xml';

        if (file_exists($filename)) {
            // キャッシュ作成日から24時間以上経過しているか
            if ( time() <= filemtime($filename) + 86400) {
                return date("Y/n/j H:i", filemtime($filename));
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function create_cache($itemcode, $str){
        if ( @file_put_contents(dirname(__FILE__) . '/cache/' . str_replace(":", "_", htmlspecialchars($itemcode)) . '.xml', $str) ){
            return date("Y/n/j H:i");
        } else {
            return false;
        }
    }

    public function is_mobile (){
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

    /**
     * get_data
     */
    public function get_data($itemcode)
    {
        mb_internal_encoding("UTF-8");
        $rakuten_options = get_option('wp_rakuten_options');
        $itemcode = htmlspecialchars($itemcode);

        $output = '';

        // キャッシュの存在確認
        if ( $update_time = $this->check_cache($itemcode) ){
            if ( $xml =  simplexml_load_string(@file_get_contents(dirname(__FILE__) . '/cache/' . str_replace(":", "_", $itemcode) . '.xml')) ){
                $output = '<!-- cache -->';

            } else {
                return "Error: Failed to load cache file.";
            }

            // キャッシュがなければ取りに行く
        } else {

            if ( $rakuten_options['developer_id'] == '' ){
                return "Error: Developers ID has not been set.";
            }

            if ( preg_match("/^(\d{3}):(\d+)/", $itemcode, $match) ){

                if ( $match[1] == '001'){ // 001 = Books
                    $url = 
                        "http://api.rakuten.co.jp/rws/3.0/rest?developerId=" . $rakuten_options['developer_id'] . 
                        "&affiliateId=" . $rakuten_options['affiliate_id'] . "&operation=BooksBookSearch&version=2009-04-15" . 
                        "&isbn=" . $match[2];
                }

            } else {
                $url = 
                    "http://api.rakuten.co.jp/rws/3.0/rest?developerId=" . $rakuten_options['developer_id'] . 
                    "&affiliateId=" . $rakuten_options['affiliate_id'] . "&operation=ItemCodeSearch&version=2010-06-30" . 
                    "&itemCode=" . $itemcode;
            }

            $origin = @file_get_contents($url);
            if ( !empty($origin) ) {

                // http://d.hatena.ne.jp/ilo/20080101/1199199418 を参考にさせていただきつつ、処理
                $origin = str_replace('header:Header', 'Header', $origin);
                $origin = str_replace('itemCodeSearch:ItemCodeSearch', 'ItemCodeSearch', $origin);
                $origin = str_replace('booksBookSearch:BooksBookSearch', 'BooksBookSearch', $origin);

                $origin = str_replace($rakuten_options['developer_id'],'DELETED', $origin);

                $xml = simplexml_load_string($origin);

                if ($xml->Header->Status == "Success"){
                    if ( !$update_time = $this->create_cache($itemcode, $origin) ){
                        return "Error: Failed to create cache data.";
                    }
                }

            } else {
                return "Error: Failed to retrieve data for this request.";

            }

        }
    }
}

$RakutenTagAdmin = new RakutenTagAdmin;
class RakutenAdmin
{
    public function __construct() {
        //add_action('admin_head', array($this, 'add_head'));
        add_action('admin_menu', array($this, 'add_menu'));
    }

    public function add_head() {
    }

    public function add_menu() {
        add_options_page(
            __('WP Rakuten Search','rakuten_search'),
            __('WP Rakuten Search','rakuten_search'),
            'manage_options',
            __FILE__,
            array($this, 'options_page')
        );
    }

    private function update_options()
    {
        $rakuten_options = array(
            'developer_id'  => esc_attr($_POST['developer_id']),
            'affiliate_id'  => esc_attr($_POST['affiliate_id']),
        );
        update_option('wp_rakuten_options', $rakuten_options);
    }

    public function options_page()
    {
        if (isset($_POST['update_option'])) {
            check_admin_referer('rakuten_plugin-options');
            $this->update_options();
            $message = _e('Options saved.');
            $saved_message = <<<EOD
<div id="message" class="updated fade">
<p><strong>{$message}</strong></p>
</div>
EOD;
            echo $saved_message;
        }

        $rakuten_options = get_option('wp_rakuten_options');
?>
<div class="wrap">

<h2><?php _e('WP Rakuten Search 設定画面', 'rakuten_search'); ?></h2>
<form name="form" method="post" action="">
<?php wp_nonce_field('rakuten_plugin-options'); ?>

<table class="form-table"><tbody>
<tr>
<th><label for="developer_id"><?php _e('楽天デベロッパーID (必須)', 'rakuten_link'); ?></label></th>
<td><input type="text" name="developer_id" id="developer_id" value="<?php echo esc_attr($rakuten_options['developer_id']); ?>" style="width: 300px;" /></td>
</tr>
<tr>
<th><label for="affiliate_id"><?php _e('楽天アフィリエイトID', 'rakuten_link'); ?></label></th>
<td><input type="text" name="affiliate_id" id="affiliate_id" value="<?php echo esc_attr($rakuten_options['affiliate_id']); ?>" style="width: 300px;" /><br />
</tr>
        </tbody></table>

        <input type="hidden" name="action" value="update" />
        <p class="submit">
        <input type="submit" name="update_option" class="button-primary" value="<?php _e('Save Changes'); ?>" />
        </p>
        </form>
        </div>
<?php
    }
}
