<?php
/**
Plugin Name: WP Rakuten Tag
Plugin URI: http://blog.newf.jp/myplugin/wp-rakuten-link/
Description: [rakuten][/rakuten]で囲まれたwordから楽天市場の個別商品を検索しエントリー上に表示します。利用にあたっては、楽天ウェブサービスのデベロッパーIDが別途必要です。WP Rakuten Linkを参考にさせていただきました。
Author: halt
Version: 0.2.0
Author URI: https://github.com/ha1t/wordpress-rakuten-tag

[更新履歴]
2020/02/12 0.2.0 : ライブラリをcomposer経由で
2014/06/12 0.1.0 : ライブラリ差し替え
2014/06/05 0.0.6 : キャッシュディレクトリの書き込み権限を管理画面から表示
2014/06/05 0.0.5 : 適用忘れのリファクタリングを追加
2013/02/10 0.0.4 : キャッシュをうまく作れなくてエラーがでていた問題を修正
2012/04/28 0.0.3 : かろうじて動くように
2012/04/27 0.0.2 : githubに公開

@todo モバイルの時はモバイルのページに飛ばすようにする
 */

mb_internal_encoding("UTF-8");
date_default_timezone_set('Asia/Tokyo');

require_once dirname(__FILE__) . '/RakutenTag.php';
require_once dirname(__FILE__) . '/RakutenTagAdmin.php';

RakutenTag::addShortCode();

$RakutenTagAdmin = new RakutenTagAdmin;
