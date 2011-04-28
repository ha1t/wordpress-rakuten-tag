<?php
/**
Plugin Name: WP Rakuten Link
Plugin URI: http://blog.newf.jp/myplugin/wp-rakuten-link/
Description: 楽天市場の個別商品を検索し、指定した商品をエントリー上に表示します。利用にあたっては、楽天ウェブサービスのデベロッパーIDが別途必要です。
Author: halt
Version: 0.0.1
Author URI: http://project-p.jp/halt/

/*
[利用方法]
	1. /wp-content/plugins/ 内に解凍したファイルをすべてアップロード
	2. cache フォルダの属性を変更(chmod)。707 とかにして、書き込みできる状態にしてください。
	3. プラグインを有効化した後、設定画面から必須項目を入力してください。
	4. 投稿画面内に商品検索画面が出るので、あとは気の向くままに。

	*. 詳しくは http://blog.newf.jp/2009/04/29/573/ を見ていただくといーです。

[更新履歴]
2009/04/29 0.1 : こさえた。

@todo モバイルの時はモバイルのページに飛ばすようにする
@todo 24h キャッシュをつける
*/

define('BLOG_URL', get_option('siteurl'));
date_default_timezone_set('Asia/Tokyo');

$RakutenLink = new RakutenLink();
class RakutenLink {

	public function __construct() {
		add_shortcode('rakuten', array($this, 'short_code'));
	}

	// エントリ内の [rakuten]code[/rakuten] を置換する。
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

        $dev_id = '';
        $afi_id = '';

        if ($limit === 1) {
          $hits = '2';
        } else {
          $hits = $limit;
        }

        // 楽天商品検索
        $api = Services_Rakuten::factory('ItemSearch', $dev_id, $afi_id);

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
            $ctl =& Anubis_Controller::getInstance();
            $backend =& $ctl->getBackend();
            $plugin =& $backend->getPlugin();
            $cm =& $plugin->getPlugin('Cachemanager', 'Localfile');

            $cm->set($keyword, $html, null, 'plugin_rakuten');
        }

        $output  = '<!-- Rakuten Plugin Start -->';
        $output .= $html;
        $output .= '<!-- Rakuten Plugin End -->';
        return $output;
    }

	public function check_cache($itemcode){

		$filename = dirname(__FILE__) . '/cache/' . str_replace(":", "_", htmlspecialchars($itemcode)) . '.xml';

		if ( file_exists($filename) ) {
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
		mb_internal_encoding("UTF-8");
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
	public function get_data($itemcode){

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

		// 表示処理部分
		if ( $xml->Header->Status == "Success" ){

			$api_category = $xml->Header->Args->Arg[3]{"value"};

			if ( $api_category == 'ItemCodeSearch' ) {
				$body = $xml->Body->$api_category->Item;
				$sImage = ($body->imageFlag == "1") ? '<img src="'.$body->smallImageUrl.'" alt="商品画像" title="' .$body->itemName.'" />' : '<img src="' . WP_PLUGIN_URL . '/' . basename(dirname(__FILE__)) . '/not_found_s.jpg" width="64" height="64" alt="商品画像なし" title="商品画像がありません" />';
				$mImage = ($body->imageFlag == "1") ? '<img src="'.$body->mediumImageUrl.'" alt="商品画像" title="' .$body->itemName.'" />' : '<img src="' . WP_PLUGIN_URL . '/' . basename(dirname(__FILE__)) . '/not_found_m.jpg" width="128" height="128" alt="商品画像なし" title="商品画像がありません" />';
				$shop_name = $body->shopName;
				$shop_url  = $body->shopUrl;

			} else {
				$body = $xml->Body->$api_category->Items->Item;

				if ( $api_category == 'BooksBookSearch' ) {
					$sImage = '<img src="'.$body->smallImageUrl.'" alt="商品画像" title="' .$body->itemName.'" />';
					$mImage = '<img src="'.$body->mediumImageUrl.'" alt="商品画像" title="' .$body->itemName.'" />';
					$shop_name = "楽天ブックス";
					$shop_url  = "http://books.rakuten.co.jp/";
				}

			}

			$caution = "このサイトで掲載されている情報は、「" . htmlspecialchars(get_option('blogname')) . "」の作成者により運営されています。価格、販売可能情報は、変更される場合があります。購入時に楽天市場店舗（www.rakuten.co.jp）に表示されている価格が、その商品の販売に適用されます。";

			$rak_words = array(
				"#ItemName#" => ( isset($body->title) ) ? $body->title : esc_attr($body->itemName),
				"#CatchCopy#" => ( isset($body->catchcopy) ) ? esc_attr($body->catchcopy) : "",
				"#ItemPrice#" => number_format("$body->itemPrice", 0) . ' 円',
				"#ItemCaption#" => (strlen($body->itemCaption) >= $rakuten_options['caption_char']) ? mb_substr($body->itemCaption, 0, $rakuten_options['caption_char'])."..." : htmlspecialchars($body->itemCaption),
				"#Url#" => ($this->is_mobile()) ? 'http://hb.afl.rakuten.co.jp/hgc/'.$rakuten_options['affiliate_id'].'/?m=' . urlencode($body->itemUrl) : $body->affiliateUrl,
				"#sImage#" => $sImage,
				"#mImage#" => $mImage,
				"#ItemStatus#" => ($body->availability == 0) ? "(販売不可)":"",
				"#Tax#" => ($body->taxFlag == 0) ? "(税込)":"(税別)",
				"#Postage#" => ($body->postageFlag == 0) ? "(送料込)":"(送料別)",
				"#CCard#" => ($body->creditCardFlag == 0) ? "(カード不可)":"(カード可)",
				"#AsuRaku#" => ($body->asurakuFlag == 0) ? "(翌日配送不可)":"(翌日配送可)",
				"#RCount#" => $body->reviewCount,
				"#RAvg#" => $body->reviewAverage,
				"#ShopName#" => $shop_name,
				"#ShopCode#" => $body->shopCode,
				"#ShopUrl#" => '<a href="' . $shop_url . '" target="' . (($rakuten_options['target_window'] == "blank") ? "_blank":"_self") . '">' . $shop_name . '</a>',
				"#Target#" => ($rakuten_options['target_window'] == 'blank') ? 'target="_blank"':'target="_self"',
				"#LastDate#" => $update_time,
				"#Caution#" => $caution,
				"#CautionTips#" => '<font class="rakuten_warn"><a class="tooltip">[ご利用にあたって]<span>'.$caution.'</span></a></font>',
			);

			// テンプレートが存在するか確認。
			if ( $rakuten_options['item_template'] != '' ) {
				$output = strtr(stripslashes($rakuten_options['item_template']), $rak_words);

			} else {
				$default_template =<<< TMP
		<div class="rakuten_image">
			<p><a href="#Url#" #Target#>#mImage#</a></p>
			#CautionTips#
		</div>
		<div class="rakuten_info">
			<p class="rakuten_itemname"><a href="#Url#" #Target#>#ItemName#</a></p>
			<p class="rakuten_caption">#ItemCaption#</p>
			<p><em>販売価格：</em> #ItemPrice# <font class="rakuten_time">(#LastDate# 更新)</font></p>
			<p><em>販売店舗：</em> #ShopUrl#</p>
		</div>
TMP;
				$output = strtr(stripslashes($default_template), $rak_words);

			}

		$credit_code =<<< CREDIT
<!-- Rakuten Web Services Attribution Snippet FROM HERE -->
<a href="http://webservice.rakuten.co.jp/" target="_blank">Supported by 楽天ウェブサービス</a>
<!-- Rakuten Web Services Attribution Snippet TO HERE -->
CREDIT;

		if ( $rakuten_options['show_credit'] != 'no' ) {
			$output.= '<div class="rakuten_credit" style="clear:both;">' . $credit_code .'</div>';
		}

		return $output;

		} else {
			return "Error: " . $xml->Header->Status;

		}

	}

} // class RakutenLink end


// class Rakuten Admin Option Menus
$RakutenAdmin = new RakutenAdmin;
class RakutenAdmin {

	public function __construct() {
		add_action('admin_head', array($this, 'add_head'));
		add_action('admin_menu', array($this, 'add_menu'));
	}

	public function add_head() {
		echo '<link rel="stylesheet" type="text/css" href="' . WP_PLUGIN_URL . '/' . basename(dirname(__FILE__)) . '/rew-san.css" />';
	}

	public function add_menu() {
		add_options_page(__('WP Rakuten Link','rakuten_link'), __('WP Rakuten Link','rakuten_link'), 'manage_options', __FILE__, array($this, 'options_page'));

		add_meta_box('wp-rakuten-link', '楽天市場 商品検索', array($this, 'itemsearchform'), 'post');
		add_meta_box('wp-rakuten-link', '楽天市場 商品検索', array($this, 'itemsearchform'), 'page');

	}

	private function cache_delete() {
		foreach (glob(WP_PLUGIN_DIR . '/' . basename(dirname(__FILE__)) . '/cache/*.xml') as $value) {
			unlink($value);
		}
	}

	private function update_options() {

		$default_template =<<< TMP
<div class="rakuten_image">
<p><a href="#Url#" #Target#>#mImage#</a></p>
#CautionTips#
</div>
<div class="rakuten_info">
<p class="rakuten_itemname"><a href="#Url#" #Target#>#ItemName#</a></p>
<p class="rakuten_caption">#ItemCaption#</p>
<p><em>販売価格：</em> #ItemPrice# <font class="rakuten_time">(#LastDate# 更新)</font></p>
<p><em>販売店舗：</em> #ShopUrl#</p>
</div>
TMP;

		$item_template = $_POST['item_template'];

		( $_POST['affiliate_id'] == '' ) ? $affiliate_id = "09e50163.3358f198.09e50164.effd7e09" : $affiliate_id = esc_attr($_POST['affiliate_id']);
		( $_POST['item_template'] == '' ) ? $item_template = $default_template : $item_template = $_POST['item_template'];

		
		( (int) $_POST['result_num'] > 30 ) ? $result_num = 30 : $result_num = (int) $_POST['result_num'];

		$rakuten_options = array(
			'developer_id'  => esc_attr($_POST['developer_id']),
			'affiliate_id'  => $affiliate_id,
			'target_window' => esc_attr($_POST['target_window']),
			'show_credit'   => esc_attr($_POST['show_credit']),
			'caption_char'  => (int) $_POST['caption_char'],
			'result_num'    => $result_num,
			'item_template' => $item_template,
		);

		update_option('wp_rakuten_options', $rakuten_options);

	}

	public function itemsearchform(){
		$rakuten_options = get_option('wp_rakuten_options');
		?>

		<script type="text/javascript"><!--

			var d_items;
			var r_keyword = new Array(3);
			var r_page = new Array(1, 1, 1);

			function rakuten_pagemove(page, cat_num){
				r_page[cat_num] = page;
				rakuten_itemsearch(r_keyword[cat_num], cat_num);
			}

			function add_this(name, code){

				if ( typeof tinyMCE != 'undefined' && ( ed = tinyMCE.activeEditor ) && !ed.isHidden() ) {
					tinyMCE.execCommand('mceInsertContent', false, '[rakuten]' + code + '[/rakuten]');

				} else {
					var s = jQuery("textarea#content").val();
					s = s + "\n" + '[rakuten]' + code + '[/rakuten]';
					jQuery("textarea#content").val(s);
				}

				jQuery(this).addClass("active");

			}

			function rakuten_itemsearch(keyword, cat_num){

				jQuery("#rakuten_itemresult_" + cat_num).html("検索中……");

				if( r_keyword[cat_num] != keyword){
					r_page[cat_num] = 1;
				}

				r_keyword[cat_num] = keyword;

				switch (cat_num){
					case 0:
						var data_obj = {
								"developerId": "<?php echo $rakuten_options['developer_id'] ?>",
								"affiliateId": "<?php echo $rakuten_options['affiliate_id'] ?>",
								"operation": "ItemSearch",
								"version" : "2010-06-30",
								"genreId" : jQuery("#rakuten_genre").val(),
								"hits": "<?php echo $rakuten_options['result_num'] ?>",
								"page": r_page[cat_num],
								"keyword": r_keyword[cat_num]
						}
						break;

					case 1:
						var data_obj = {
								"developerId": "<?php echo $rakuten_options['developer_id'] ?>",
								"affiliateId": "<?php echo $rakuten_options['affiliate_id'] ?>",
								"operation": "BooksTotalSearch",
								"version" : "2009-04-15",
								"booksGenreId" : jQuery("#rakuten_book_genre").val(),
								"hits": "<?php echo $rakuten_options['result_num'] ?>",
								"page": r_page[cat_num],
								"keyword": r_keyword[cat_num],
								"genreInformationFlag": "1"
						}
						break;
					default:
						return false;
						break;
				}

				jQuery.ajax({

					url: "http://api.rakuten.co.jp/rws/3.0/json?",
					data: data_obj,
					dataType: "jsonp",
					jsonp: "callBack",
					error: function(){
						jQuery("#rakuten_itemresult_" + cat_num).html("検索処理中にエラーが発生しました。");
						return false;
					},
					success: function(data) {
							if (data.Header.Status != "Success" ){
								jQuery("#rakuten_itemresult_" + cat_num).html("商品が見つかりませんでした。");
								return false;
							}

							switch(cat_num){
								case 0:
									var head = data.Body.ItemSearch;
									var items = data.Body.ItemSearch.Items.Item;
									break;

								case 1:
									var head = data.Body.BooksTotalSearch;
									var items = data.Body.BooksTotalSearch.Items.Item;
									var genre = data.Body.BooksTotalSearch.GenreInformation.current[0];
									break;
							}

							jQuery("#rakuten_itemresult_" + cat_num).html("");
							jQuery("#rakuten_itemresult_" + cat_num).append(head.count + " 件の商品が見つかりました！ (" + head.page + "/" + head.pageCount + "ページ)&nbsp;");

							jQuery("#rakuten_itemresult_" + cat_num).append('<input type="button" value="前のページ" id="rakuten_beforepage" class="button-secondary action" onclick="rakuten_pagemove(' + (r_page[cat_num] - 1) + ', ' + cat_num + ')">');
							jQuery("#rakuten_itemresult_" + cat_num).append('<input type="button" value="次のページ" id="rakuten_nextpage" class="button-secondary action" onclick="rakuten_pagemove(' + (r_page[cat_num] + 1) + ', ' + cat_num + ')">');
							jQuery("#rakuten_itemresult_" + cat_num).append('<br />');
							jQuery('<ul class="rakuten_itemlist" />').appendTo("div#rakuten_itemresult_" + cat_num);

							// ページ送りの処理とか
							if ( parseInt(r_page[cat_num]) <= 1 ){
								jQuery("#rakuten_beforepage").remove();
							}
							if ( parseInt(r_page[cat_num]) >= head.pageCount ){
								jQuery("#rakuten_nextpage").remove();
							}

							// アイテムの表示部分
							jQuery.each(items, function(i, item) {

								var tips = "";
								var content = "";

								switch(cat_num){
									case 0:

										if ( items[i].imageFlag == "1" ) {
											var img = "<img src='" + items[i].smallImageUrl + "' class=\"rakuten_itemimg\" title=\"" + items[i].itemName + "\" />";

										} else {
											var img = "[商品画像なし]";
										}

										tips =
										items[i].itemName;

										content = 
										"<a href=\"" + items[i].itemUrl + "\" target=\"_blank\" class=\"tooltip\">" + img + "<span>" + tips + "</span><\/a><br />" + 
										'価格：' + items[i].itemPrice + ' 円<br />' +
										'<a href="' + items[i].shopUrl + '" target="_blank">' + items[i].shopName + '</a><br /> ' +
										'<a href="#rak_menu_tab" style="font-size: 8pt" onclick="add_this(\'' + items[i].itemName + '\',\'' + items[i].itemCode + '\')" title="[rakuten]'+ items[i].itemCode + '[/rakuten]">[挿入]<\/a>';

										jQuery("<li/>").append(content).appendTo("#rakuten_itemresult_" + cat_num + "> ul");
										break;

									case 1:
										var img = "<img src='" + items[i].smallImageUrl + "' class=\"rakuten_itemimg\" title=\"" + items[i].itemName + "\" />";
										if (items[i].artistName != ""){
											content = 
											items[i].title + '<br />' +
											'<a href="#rak_menu_tab" style="font-size: 8pt" onclick="add_this(\'' + items[i].title + '\',\'' + genre.booksGenreId + ':' + items[i].jan + '\')" title="[rakuten]'+ genre.booksGenreId + ':' + items[i].jan + '[/rakuten]">[挿入]<\/a>';

											tips = 'カテゴリ：' + genre.booksGenreName + '<br />アーティスト：' + items[i].artistName + '<br />発売元：' + items[i].label + "<br />" + items[i].itemCaption;

										} else if (items[i].label != "") {
											content = 
											'販売元：' + items[i].label + '<br />' +
											'<a href="#rak_menu_tab" style="font-size: 8pt" onclick="add_this(\'' + items[i].title + '\',\'' + genre.booksGenreId + ':' + items[i].jan + '\')" title="[rakuten]'+ genre.booksGenreId + ':' + items[i].jan + '[/rakuten]">[挿入]<\/a>';

											tips = 'カテゴリ：' + genre.booksGenreName + '<br />タイトル：' + items[i].title + '<br />' + items[i].itemCaption;

										} else {
											content = 
											'著者：' + items[i].author + '<br />' +
											'<a href="#rak_menu_tab" style="font-size: 8pt" onclick="add_this(\'' + items[i].title + '\', \'' + genre.booksGenreId + ':' + items[i].isbn + '\')" title="[rakuten]'+ genre.booksGenreId + ':' + items[i].isbn + '[/rakuten]">[挿入]<\/a>';

											tips = 'カテゴリ：' + genre.booksGenreName + '<br />タイトル：' + items[i].title + '<br />出版社：' + items[i].publisherName + '<br />' + items[i].itemCaption;

										}

										content = 
										"<a href=\"" + items[i].itemUrl + "\" target=\"_blank\" class=\"tooltip\">" + img + "<span>" + tips + "<\/span><\/a><br />" + 
										'価格：' + items[i].itemPrice + ' 円<br />' + content;


										jQuery("<li/>").append(content).appendTo("#rakuten_itemresult_" + cat_num + "> ul");
										break;
								}

							});

					}

				});

			}

			jQuery(function(){

				jQuery(".rak_tablist li a").click(function() {
					jQuery(".rak_active").removeClass("rak_active");
					jQuery(".rak_form_contents").slideUp(50);
					jQuery('#' + jQuery(this).attr("class")).slideDown(50);
					jQuery(this).addClass("rak_active");
				});

				jQuery.ajax({
					url: "http://api.rakuten.co.jp/rws/3.0/json?",
					data: {
						"developerId": "<?php echo $rakuten_options['developer_id'] ?>",
						"operation": "GenreSearch",
						"version" : "2007-04-11",
						"genreId": "0"
					},
					dataType: "jsonp",
					jsonp: "callBack",
					error: function(){
						jQuery("#rakuten_itemresult").html("ジャンルデータ取得時にエラーが発生しました。");
						return false;
					},
					success: function(data) {
							if (data.Header.Status != "Success" ){
								jQuery("#rakuten_itemresult").html("ジャンルデータを取得できませんでした。");
								return false;
							}

							var head = data.Body.GenreSearch;
							jQuery("#rakuten_genre").children().remove();
							jQuery("#rakuten_genre").append( jQuery('<option>').attr({ value: "0" }).text("すべての商品") );
							jQuery.each(data.Body.GenreSearch.child, function(i, child) {

								jQuery("#rakuten_genre").append( jQuery('<option>').attr({ value: head.child[i].genreId }).text(head.child[i].genreName) );
								jQuery("#rakuten_genre").width();

							});

					}
				});

				jQuery.ajax({
					url: "http://api.rakuten.co.jp/rws/3.0/json?",
					data: {
						"developerId": "<?php echo $rakuten_options['developer_id'] ?>",
						"operation": "BooksGenreSearch",
						"version" : "2009-03-26",
						"booksGenreId": "000"
					},
					dataType: "jsonp",
					jsonp: "callBack",
					error: function(){
						jQuery("#rakuten_bookresult").html("楽天ブックスのジャンルデータ取得時にエラーが発生しました。");
						return false;
					},
					success: function(data) {
							if (data.Header.Status != "Success" ){
								jQuery("#rakuten_bookresult").html("楽天ブックスのジャンルデータを取得できませんでした。");
								return false;
							}

							var head = data.Body.BooksGenreSearch;
							jQuery("#rakuten_book_genre").children().remove();
							//jQuery("#rakuten_book_genre").append( jQuery('<option>').attr({ value: "000" }).text("すべての商品") );
							jQuery.each(data.Body.BooksGenreSearch.child, function(i, child) {
								if ( head.child[i].genreId == "001" ) {
									jQuery("#rakuten_book_genre").append( jQuery('<option>').attr({ value: head.child[i].genreId }).text(head.child[i].genreName) );
								}
								jQuery("#rakuten_book_genre").width();
							});

					}

				});

			});

		// --></script>

		<div class="rak_menu_tab">
			<ul class="rak_tablist">
				<li><a href="#rak_menu_tab" class="rak_form_market rak_active">楽天市場</a></li>
				<li><a href="#rak_menu_tab" class="rak_form_books">楽天ブックス</a></li>
			</ul>

			<div id="rakuten_tempcode" style="margin: 0; padding: 0; overflow: auto; display: none;"></div>

			<div style="display: block;" id="rak_form_market" class="rak_form_contents">
				<input type="text" id="rakuten_keyword" style="width:200px;"/>
				<select name="genre_select" id="rakuten_genre">
				<option value="000000">すべての商品</option>
				</select>
				<input type="button" value="検索" class="button-secondary action" onclick="rakuten_itemsearch(rakuten_keyword.value, 0)" />

				<div id="rakuten_itemresult_0" style="margin: 0.3em; padding: 0.3em; overflow: auto; "></div>

			</div>
			<div style="display: none;" id="rak_form_books" class="rak_form_contents">
				<input type="text" id="rakuten_keyword_book" style="width:200px;"/>
				<select name="book_genre_select" id="rakuten_book_genre">
				<option value="000000">すべての商品</option>
				</select>
				<input type="button" value="検索" class="button-secondary action" onclick="rakuten_itemsearch(rakuten_keyword_book.value, 1)" />

				<div id="rakuten_itemresult_1" style="margin: 0.3em; padding: 0.3em; overflow: auto; "></div>

			</div>

		</div>

		<input type="hidden" id="latest_rakutenkeyword" value="" />
		<input type="hidden" id="rakuten_page" value="1" />

		<?php

	}

	public function options_page () {
		if (isset($_POST['update_option'])) {
			check_admin_referer('rakuten_plugin-options');
			$this->update_options(); ?>

			<div id="message" class="updated fade"><p><strong><?php _e('Options saved.'); ?></strong></p></div>

		<?php }
		if (isset($_POST['cache_delete'])) {
			check_admin_referer('rakuten_plugin-options');
			$this->cache_delete(); ?>
			<div id="message" class="updated fade"><p><strong><?php _e('キャッシュファイルを削除しました。', 'rakuten_link'); ?></strong></p></div>

		<?php }

		$rakuten_options = get_option('wp_rakuten_options');
$default_template =<<< TMP
<div class="rakuten_image">
<p><a href="#Url#" #Target#>#mImage#</a></p>
#CautionTips#
</div>
<div class="rakuten_info">
<p class="rakuten_itemname"><a href="#Url#" #Target#>#ItemName#</a></p>
<p class="rakuten_caption">#ItemCaption#</p>
<p><em>販売価格：</em> #ItemPrice# <font class="rakuten_time">(#LastDate# 更新)</font></p>
<p><em>販売店舗：</em> #ShopUrl#</p>
</div>
TMP;
		?>
		<div class="wrap">

		<h2><?php _e('WP Rakuten Link 設定画面', 'rakuten_link'); ?></h2>
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
			<font style="font-size: 8pt;"><?php _e('※未記入の場合、制作者の ID が使用されます。', 'rakuten_link'); ?></font></td>
		</tr>
		<tr>
		<th><label><?php _e('商品選択時の表示方法', 'rakuten_link'); ?></label></th>
			<td>
			<label title="同じウィンドウで開く"><input type="radio" name="target_window" id="target_window" value="self" <?php checked('self', $rakuten_options['target_window']) ?> />
			<?php _e('同じウィンドウで開く', 'rakuten_link'); ?></label><br />
			<label title="新しいウィンドウで開く"><input type="radio" name="target_window" id ="target_window" value="blank" <?php checked('blank', $rakuten_options['target_window']) ?> />
			<?php _e('新しいウィンドウで開く', 'rakuten_link'); ?></label>
			</td>
		</tr>
		<tr>
		<th><label><a href="http://webservice.rakuten.co.jp/credit/" target="_blank"><?php _e('クレジットの表示', 'rakuten_link'); ?></a></label></th>
		<td>
			<label title="クレジットを表示する"><input type="radio" name="show_credit" id="show_credit" value="yes" <?php checked('yes', $rakuten_options['show_credit']) ?> />
			<?php _e('クレジットを表示する', 'rakuten_link'); ?></label><br />
			<label title="クレジットを表示しない"><input type="radio" name="show_credit" id ="show_credit" value="no" <?php checked('no', $rakuten_options['show_credit']) ?> />
			<?php _e('クレジットを表示しない', 'rakuten_link'); ?></label>
			<font style="font-size: 8pt;"><?php _e('(「表示しない」に設定した場合、別途サイト上に記載する必要があります。)', 'rakuten_link'); ?></font>
		</td>
		</tr>
		<tr>
			<th><labe for="caption_char"><?php _e('表示時の商品説明文字数', 'rakuten_link'); ?></label></th>
			<td><input type="text" name="caption_char" id="caption_char" class="regular-text" value="<?php echo ($rakuten_options['caption_char'] >= 1) ? $rakuten_options['caption_char']:80; ?>" style="width: 40px;" /></td>
		</tr>
		<tr>
		<th><labe for="result_num"><?php _e('アイテム検索時の最大件数', 'rakuten_link'); ?></label>
		</th>
		<td>
			<input type="text" name="result_num" id="result_num" class="small-text" value="<?php echo ($rakuten_options['result_num'] >= 1) ? $rakuten_options['result_num']:10; ?>" style="width: 40px;" /><br />
			<?php _e('(最大 30 件)', 'rakuten_link'); ?>
		</td>
		</tr>

		<tr>
		<th><labe for="item_template"><?php _e('アイテム表示テンプレート', 'rakuten_link'); ?></label></th>
		<td>
		<textarea name="item_template" id="item_template" rows="10" cols="120"><?php 

				if ($rakuten_options['item_template'] == ''){ 
					echo esc_attr($default_template);
				
				} else {
					echo stripslashes($rakuten_options['item_template']);
				}

			?></textarea>
			<br />
			<script type="text/javascript">

				jQuery.fn.extend({
					insertAtCaret: function(v) {
						var o = this.get(0);
						o.focus();
						if (jQuery.browser.msie) {
							var r = document.selection.createRange();
							r.text = v;
							r.select();
						} else {
							var s = o.value;
							var p = o.selectionStart;
							var np = p + v.length;
							o.value = s.substr(0, p) + v + s.substr(p);
							o.setSelectionRange(np, np);
						}
					}
				});

			</script>
			<input type="button" value="商品名" onclick="jQuery('#item_template').insertAtCaret('#ItemName#');" title="商品名を表示します"/>
			<input type="button" value="キャッチコピー" onclick="jQuery('#item_template').insertAtCaret('#CatchCopy#');" title="キャッチコピーを表示します。(楽天市場のみ)"/>
			<input type="button" value="販売価格" onclick="jQuery('#item_template').insertAtCaret('#ItemPrice#');" title="「円」を付与した、価格を表示します" />
			<input type="button" value="商品説明" onclick="jQuery('#item_template').insertAtCaret('#ItemCaption#');" title="商品の説明文を表示します" />
			<input type="button" value="商品 URL" onclick="jQuery('#item_template').insertAtCaret('#Url#');" title="アフィリエイト ID を含む、商品の購入ページの URL を表示します" />
			<input type="button" value="商品画像(小)" onclick="jQuery('#item_template').insertAtCaret('#sImage#');" title="64x64 の商品画像を表示します" />
			<input type="button" value="商品画像(大)" onclick="jQuery('#item_template').insertAtCaret('#mImage#');" title="128x128 の商品画像を表示します" /><br />
			<input type="button" value="販売状況" onclick="jQuery('#item_template').insertAtCaret('#ItemStatus#');" title="「販売不可」となっている商品の場合には、「販売不可」と表示します" />
			<input type="button" value="税金" onclick="jQuery('#item_template').insertAtCaret('#Tax#');" title="税込みかどうか、「(税込)」「(税別)」のいずれかを表示します" />
			<input type="button" value="送料" onclick="jQuery('#item_template').insertAtCaret('#Postage#');" title="送料が含まれるかどうか、「(送料込)」「(送料別)」のいずれかを表示します" />
			<input type="button" value="カード" onclick="jQuery('#item_template').insertAtCaret('#CCard#');" title="カードでの支払いが可能かどうか、「(カード不可)」「(カード可)」のいずれかを表示します" />
			<input type="button" value="あす楽" onclick="jQuery('#item_template').insertAtCaret('#AsuRaku#');" title="「あす楽」対応かどうか、「(翌日配送不可)」「(翌日配送可)」のいずれかを表示します" />
			<input type="button" value="レビュー件数" onclick="jQuery('#item_template').insertAtCaret(' #RCount#');" title="商品に寄せられたレビュー数を表示します" />
			<input type="button" value="レビュー平均" onclick="jQuery('#item_template').insertAtCaret('#RAvg#');" title="商品に寄せられたレビューの平均値を表示します" />
			<input type="button" value="店舗名" onclick="jQuery('#item_template').insertAtCaret('#ShopName#');" title="商品を取り扱っている店舗名を表示します" />
			<input type="button" value="店舗リンク" onclick="jQuery('#item_template').insertAtCaret('#ShopUrl#');" title="商品を取り扱っている店舗の URL を表示し、店舗名でのリンクを表示します" /><br />
			<input type="button" value="リンクを開くウィンドウ" onclick="jQuery('#item_template').insertAtCaret('#Target#');" title="リンクを開くウィンドウを指定します。 #Url# とセットで使用。" />
			<input type="button" value="更新日" onclick="jQuery('#item_template').insertAtCaret('#LastDate#');" title="データを取得した日時を表示します" />
			<input type="button" value="注意文" onclick="jQuery('#item_template').insertAtCaret('#Caution#');" title="掲載した情報に関する注意文を表示します" />
			<input type="button" value="注意文(ツールチップ)" onclick="jQuery('#item_template').insertAtCaret('#CautionTips#');" title="掲載情報の注意文をツールチップを用いて、「ご利用にあたって」のリンクで表示させます" />
		</td>
		</tr>

		</tbody></table>

		<input type="hidden" name="action" value="update" />
		<p class="submit">
		<input type="submit" name="update_option" class="button-primary" value="<?php _e('Save Changes'); ?>" />
		</p>
		</form>

		<form name="form" method="post" action="">
		<?php wp_nonce_field('rakuten_plugin-options'); ?>
		<input type="hidden" name="action" value="cache_delete" />
		<input type="submit" name="cache_delete" value="キャッシュを削除する" />
		</form>

		</div>
		<?php
	}


} // End Class "Admin"


?>
