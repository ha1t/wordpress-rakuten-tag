<?php
/**
Plugin Name: WP Rakuten Tag
Plugin URI: http://blog.newf.jp/myplugin/wp-rakuten-link/
Description: [rakuten][/rakuten]で囲まれたwordから楽天市場の個別商品を検索しエントリー上に表示します。利用にあたっては、楽天ウェブサービスのデベロッパーIDが別途必要です。WP Rakuten Linkを参考にさせていただきました。
Author: halt
Version: 0.0.3
Author URI: http://project-p.jp/halt/

[更新履歴]
2012/04/28 0.0.3 : かろうじて動くように
2012/04/27 0.0.2 : githubに公開

@todo モバイルの時はモバイルのページに飛ばすようにする
@todo Services_Rakutenへの依存をやめる
 */

mb_internal_encoding("UTF-8");
date_default_timezone_set('Asia/Tokyo');

require_once dirname(__FILE__) . '/RakutenTag.php';

RakutenTag::addShortCode();

$RakutenTagAdmin = new RakutenTagAdmin;
