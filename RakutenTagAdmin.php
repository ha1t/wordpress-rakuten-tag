<?php
/**
 *
 *
 */
class RakutenTagAdmin
{
    public function __construct() {
        //add_action('admin_head', array($this, 'add_head'));
        add_action('admin_menu', [$this, 'add_menu']);
    }

    public function add_head() {
    }

    public function add_menu() {
        add_options_page(
            'WP Rakuten Tag',
            'WP Rakuten Tag',
            'manage_options',
            __FILE__,
            [$this, 'options_page']
        );
    }

    private function update_options()
    {
        $rakuten_options = [
            'developer_id'  => esc_attr($_POST['developer_id']),
            'affiliate_id'  => esc_attr($_POST['affiliate_id']),
        ];
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
<h2>WP Rakuten Tag 設定画面</h2>
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
