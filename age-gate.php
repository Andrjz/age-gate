<?php
/**
 * Plugin Name: Age Gate by PluginCams
 * Description: Country-based 18+ age gate with optional full blocking and customizable colors/texts.
 * Version:     1.1.0
 * Author:      Andrjz
 * Author URI:  https://github.com/Andrjz/age-gate
 * Company:     PluginCams
 * Company URI: https://plugincams.com
 */

if (!defined('ABSPATH')) exit;

class AGC_Plugin {
    const OPTION = 'agc_settings';

    public function __construct() {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
		add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_settings_link']);
		
		// Settings link
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_settings_link']);

    // Company link in plugin row meta
    add_filter('plugin_row_meta', [$this, 'add_company_meta'], 10, 2);
   
	}

   public function add_company_meta($links, $file) {
    if ($file === plugin_basename(__FILE__)) {
        $links[] = '<a href="https://plugincams.com" target="_blank" rel="noopener">Visit Plugin Site</a>';
    }
    return $links;
	
    }
	
    public function add_settings_link($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=agc') . '">' . __('Settings') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
   }	   
	
    public static function defaults() {
        return [
            // Countries with popup (ISO2, comma-separated)
            'popup_countries' => 'US,CA,AU,NZ,AL,AD,AM,AT,AZ,BY,BE,BA,BG,HR,CY,CZ,DK,EE,FI,FR,GE,DE,GR,HU,IS,IE,IT,LV,LI,LT,LU,MT,MD,MC,ME,NL,MK,NO,PL,PT,RO,RU,SM,RS,SK,SI,ES,SE,CH,TR,UA,GB,VA',
            // Countries fully blocked
            'blocked_countries' => '',
            // URLs
            'redirect_url' => 'https://example.com',
            'exit_url'     => 'https://www.google.com',

            // Texts
            'title_popup' => 'Age Verification Required',
            'text_popup'  => 'This website contains adult content and is restricted to viewers 18 years of age or older. By entering, you confirm that you meet the age requirement and agree to our Terms of Service.',
            'btn_exit'    => 'Exit',
            'btn_enter'   => 'Confirm & Enter',
            'legal'       => 'All models appearing on this website are 18 years or older. By entering you accept our Privacy Policy and Cookie Notice.',
            'title_block' => 'Access Restricted',
            'text_block'  => 'We are unable to provide our services to visitors from your location due to regional restrictions and compliance requirements.',
            'btn_alt'     => 'Go to Alternative',

            // Colors
            'color_overlay'      => 'rgba(0,0,0,0.85)',
            'color_card_bg'      => '#121212',
            'color_card_border'  => '#2a2a2a',
            'color_title'        => '#ffffff',
            'color_text'         => '#cccccc',
            'color_legal'        => '#777777',
            'color_exit_bg'      => '#2a2a2a',
            'color_exit_border'  => '#3a3a3a',
            'color_exit_text'    => '#ffffff',
            'color_enter_bg'     => '#1175c7',
            'color_enter_border' => '#1175c7',
            'color_enter_text'   => '#ffffff',
            'color_hover_bg'     => '#222222',

            // Font & remember settings
            'font_url'           => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap',
            'use_session'        => 1,  // 1=sessionStorage, 0=localStorage
            'remember_hours'     => 24
        ];
    }

    public function get_settings() {
        return wp_parse_args(get_option(self::OPTION, []), self::defaults());
    }

    public function menu() {
        add_options_page('Age Gate (Country)', 'Age Gate (Country)', 'manage_options', 'agc', [$this, 'settings_page']);
    }

    public function register_settings() {
        register_setting('agc_group', self::OPTION, [$this, 'sanitize']);
    }

    public function sanitize($in) {
        $d = self::defaults();
        $out = [];
        foreach ($d as $k => $v) {
            if (!isset($in[$k])) { $out[$k] = $v; continue; }
            $val = is_string($in[$k]) ? wp_kses_post(trim($in[$k])) : $in[$k];

            switch ($k) {
                case 'popup_countries':
                case 'blocked_countries':
                    $val = strtoupper(preg_replace('/[^A-Za-z0-9,]/', '', $val));
                    $val = implode(',', array_filter(array_map('trim', explode(',', $val))));
                    break;
                case 'redirect_url':
                case 'exit_url':
                    $val = esc_url_raw($val);
                    break;
                case 'use_session':
                    $val = intval($val) ? 1 : 0;
                    break;
                case 'remember_hours':
                    $val = max(1, intval($val));
                    break;
                default:
                    // texts/colors pass after basic sanitize
                    break;
            }
            $out[$k] = ($val === '' && isset($d[$k])) ? $d[$k] : $val;
        }
        return $out;
    }

    public function settings_page() {
        $s = $this->get_settings();
        ?>
        <div class="wrap">
            <h1>Age Gate (Country)</h1>
            <form method="post" action="options.php">
                <?php settings_fields('agc_group'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">Popup Countries</th>
                        <td>
                            <input type="text" name="<?php echo self::OPTION; ?>[popup_countries]" value="<?php echo esc_attr($s['popup_countries']); ?>" class="regular-text" />
                            <p class="description">ISO 3166-1 alpha-2, comma-separated (e.g., ES,FR,US).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Blocked Countries</th>
                        <td>
                            <input type="text" name="<?php echo self::OPTION; ?>[blocked_countries]" value="<?php echo esc_attr($s['blocked_countries']); ?>" class="regular-text" />
                            <p class="description">Users in these countries will see “Access Restricted”.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Alternative URL (Blocked)</th>
                        <td><input type="url" name="<?php echo self::OPTION; ?>[redirect_url]" value="<?php echo esc_attr($s['redirect_url']); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Exit Button URL</th>
                        <td><input type="url" name="<?php echo self::OPTION; ?>[exit_url]" value="<?php echo esc_attr($s['exit_url']); ?>" class="regular-text" /></td>
                    </tr>

                    <tr>
                        <th scope="row">Remember Choice</th>
                        <td>
                            <label><input type="radio" name="<?php echo self::OPTION; ?>[use_session]" value="1" <?php checked($s['use_session'],1); ?> /> sessionStorage (until browser closes)</label><br>
                            <label><input type="radio" name="<?php echo self::OPTION; ?>[use_session]" value="0" <?php checked($s['use_session'],0); ?> /> localStorage (hours):</label>
                            <input type="number" min="1" name="<?php echo self::OPTION; ?>[remember_hours]" value="<?php echo intval($s['remember_hours']); ?>" style="width:80px;">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Font (optional)</th>
                        <td><input type="url" name="<?php echo self::OPTION; ?>[font_url]" value="<?php echo esc_attr($s['font_url']); ?>" class="regular-text" /></td>
                    </tr>

                    <tr>
                        <th scope="row">Popup Texts</th>
                        <td>
                            <label>Title<br><input type="text" name="<?php echo self::OPTION; ?>[title_popup]" value="<?php echo esc_attr($s['title_popup']); ?>" class="regular-text" /></label><br><br>
                            <label>Description<br><textarea name="<?php echo self::OPTION; ?>[text_popup]" rows="4" class="large-text"><?php echo esc_textarea($s['text_popup']); ?></textarea></label><br><br>
                            <label>Exit Button Label<br><input type="text" name="<?php echo self::OPTION; ?>[btn_exit]" value="<?php echo esc_attr($s['btn_exit']); ?>" class="regular-text" /></label><br><br>
                            <label>Enter Button Label<br><input type="text" name="<?php echo self::OPTION; ?>[btn_enter]" value="<?php echo esc_attr($s['btn_enter']); ?>" class="regular-text" /></label><br><br>
                            <label>Legal Notice<br><textarea name="<?php echo self::OPTION; ?>[legal]" rows="3" class="large-text"><?php echo esc_textarea($s['legal']); ?></textarea></label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Block Texts</th>
                        <td>
                            <label>Title<br><input type="text" name="<?php echo self::OPTION; ?>[title_block]" value="<?php echo esc_attr($s['title_block']); ?>" class="regular-text" /></label><br><br>
                            <label>Description<br><textarea name="<?php echo self::OPTION; ?>[text_block]" rows="3" class="large-text"><?php echo esc_textarea($s['text_block']); ?></textarea></label><br><br>
                            <label>Alternative Button Label<br><input type="text" name="<?php echo self::OPTION; ?>[btn_alt]" value="<?php echo esc_attr($s['btn_alt']); ?>" class="regular-text" /></label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Colors</th>
                        <td>
                            <?php
                            $color_fields = [
                                'color_overlay'      => 'Overlay (background)',
                                'color_card_bg'      => 'Card background',
                                'color_card_border'  => 'Card border',
                                'color_title'        => 'Title',
                                'color_text'         => 'Text',
                                'color_legal'        => 'Legal',
                                'color_exit_bg'      => 'Exit button bg',
                                'color_exit_border'  => 'Exit button border',
                                'color_exit_text'    => 'Exit button text',
                                'color_enter_bg'     => 'Enter button bg',
                                'color_enter_border' => 'Enter button border',
                                'color_enter_text'   => 'Enter button text',
                                'color_hover_bg'     => 'Buttons hover bg'
                            ];
                            foreach ($color_fields as $k=>$label): ?>
                                <label style="display:inline-block;min-width:200px;margin:3px 12px 3px 0"><?php echo esc_html($label); ?>
                                    <input type="text" name="<?php echo self::OPTION; ?>[<?php echo $k; ?>]" value="<?php echo esc_attr($s[$k]); ?>" class="regular-text" style="width:180px" />
                                </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function enqueue() {
        if (is_admin()) return;

        $s = $this->get_settings();

        // Prepare data for inline JS
        $data = [
            'POPUP'    => array_filter(array_map('trim', explode(',', $s['popup_countries']))),
            'BLOCKED'  => array_filter(array_map('trim', explode(',', $s['blocked_countries']))),
            'REDIRECT' => $s['redirect_url'],
            'TEXTS' => [
                'title_popup' => $s['title_popup'],
                'text_popup'  => $s['text_popup'],
                'btn_exit'    => $s['btn_exit'],
                'btn_enter'   => $s['btn_enter'],
                'legal'       => $s['legal'],
                'title_block' => $s['title_block'],
                'text_block'  => $s['text_block'],
                'btn_alt'     => $s['btn_alt'],
                'exit_url'    => $s['exit_url'],
            ],
            'COLORS' => [
                'overlay'     => $s['color_overlay'],
                'card_bg'     => $s['color_card_bg'],
                'card_border' => $s['color_card_border'],
                'title'       => $s['color_title'],
                'text'        => $s['color_text'],
                'legal'       => $s['color_legal'],
                'exit_bg'     => $s['color_exit_bg'],
                'exit_border' => $s['color_exit_border'],
                'exit_text'   => $s['color_exit_text'],
                'enter_bg'    => $s['color_enter_bg'],
                'enter_border'=> $s['color_enter_border'],
                'enter_text'  => $s['color_enter_text'],
                'hover_bg'    => $s['color_hover_bg'],
            ],
            'FONT' => $s['font_url'],
            'STORAGE' => [
                'use_session' => (int)$s['use_session'],
                'hours'       => (int)$s['remember_hours'],
            ],
            // Fast country header via Cloudflare (if present)
            'PHP_COUNTRY' => isset($_SERVER['HTTP_CF_IPCOUNTRY']) ? sanitize_text_field($_SERVER['HTTP_CF_IPCOUNTRY']) : '',
        ];

        add_action('wp_footer', function() use ($data) {
            $json = wp_json_encode($data);
            ?>
<script id="agc-data" type="application/json"><?php echo $json; ?></script>
<script>
(function(){
  try {
    const conf = JSON.parse(document.getElementById('agc-data').textContent);

    // Remember logic (session vs local hours)
    const KEY = 'adult_ok';
    const rememberOK = () => {
      if (conf.STORAGE.use_session) {
        sessionStorage.setItem(KEY, '1');
      } else {
        const until = Date.now() + (conf.STORAGE.hours * 3600 * 1000);
        localStorage.setItem(KEY, String(until));
      }
    };
    const isRemembered = () => {
      if (conf.STORAGE.use_session) return !!sessionStorage.getItem(KEY);
      const v = localStorage.getItem(KEY);
      if (!v) return false;
      const until = parseInt(v, 10);
      if (isNaN(until) || Date.now() > until) { localStorage.removeItem(KEY); return false; }
      return true;
    };

    // Skip bots + already accepted
    const ua = navigator.userAgent;
    if (/bot|crawl|spider|slurp|bingpreview|yandex|duckduckbot|facebookexternalhit|twitterbot/i.test(ua)) return;
    if (isRemembered()) return;

    // Optional font
    if (conf.FONT) {
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = conf.FONT;
      document.head.appendChild(link);
    }

    // CSS
    const css = `
      #adult-popup,#adult-popup *{box-sizing:border-box}
      #adult-popup{
        position:fixed;inset:0;width:100%;height:100%;
        background:${conf.COLORS.overlay};
        display:none;justify-content:center;align-items:center;overflow-y:auto;z-index:9999;padding:20px
      }
      #adult-popup .popup-container{
        background:${conf.COLORS.card_bg};
        border:1px solid ${conf.COLORS.card_border};
        border-radius:10px;box-shadow:0 8px 32px rgba(0,0,0,.3);
        width:min(90%,400px);padding:30px;text-align:center;color:${conf.COLORS.text};
        font-family:'Inter',system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,'Helvetica Neue',Arial,sans-serif;
        max-height:90vh;overflow-y:auto
      }
      #adult-popup .popup-container h2{
        margin:0 0 18px;font-size:20px;font-weight:600;letter-spacing:.5px;text-transform:uppercase;color:${conf.COLORS.title}
      }
      #adult-popup .divider{height:1px;background:linear-gradient(90deg,transparent,#333,transparent);margin:20px 0}
      #adult-popup p{font-size:14px;line-height:1.6;margin-bottom:24px;color:${conf.COLORS.text}}
      #adult-popup .button-container{display:flex;gap:12px}
      #adult-popup button{flex:1;padding:12px;border:none;border-radius:6px;font-weight:500;font-size:14px;cursor:pointer;transition:background .25s;letter-spacing:.5px}
      #adult-popup .exit-btn{background:${conf.COLORS.exit_bg};color:${conf.COLORS.exit_text};border:1px solid ${conf.COLORS.exit_border}}
      #adult-popup .exit-btn:hover{background:${conf.COLORS.hover_bg}}
      #adult-popup .enter-btn{background:${conf.COLORS.enter_bg};color:${conf.COLORS.enter_text};border:1px solid ${conf.COLORS.enter_border}}
      #adult-popup .enter-btn:hover{background:${conf.COLORS.hover_bg};border-color:${conf.COLORS.hover_bg}}
      #adult-popup .legal-notice{font-size:11px;margin-top:24px;color:${conf.COLORS.legal};line-height:1.5}
      @media (max-width:480px){#adult-popup{padding:10px}#adult-popup .popup-container{padding:20px}}
    `;
    const style = document.createElement('style');
    style.textContent = css;
    document.head.appendChild(style);

    // Root node
    const popup = document.createElement('div');
    popup.id = 'adult-popup';
    document.body.appendChild(popup);

    // blur helpers
    const blurOn = () => Array.from(document.body.children).filter(el => el !== popup).forEach(el => el.style.filter='blur(4px)');
    const blurOff = () => Array.from(document.body.children).filter(el => el !== popup).forEach(el => el.style.filter='');

    // Renderers
    const renderPopup = () => {
      popup.innerHTML = `
        <div class="popup-container">
          <h2>${conf.TEXTS.title_popup}</h2>
          <div class="divider"></div>
          <p>${conf.TEXTS.text_popup}</p>
          <div class="button-container">
            <button class="exit-btn" id="agc-exit">${conf.TEXTS.btn_exit}</button>
            <button class="enter-btn" id="agc-enter">${conf.TEXTS.btn_enter}</button>
          </div>
          <div class="legal-notice">${conf.TEXTS.legal}</div>
        </div>`;
      popup.style.display = 'flex'; blurOn();
      document.getElementById('agc-exit').addEventListener('click', function(){
        window.location.href = conf.TEXTS.exit_url || 'https://www.google.com';
      });
      document.getElementById('agc-enter').addEventListener('click', function(){
        rememberOK(); popup.style.display='none'; blurOff();
      });
    };

    const renderBlocked = () => {
      popup.innerHTML = `
        <div class="popup-container">
          <h2>${conf.TEXTS.title_block}</h2>
          <div class="divider"></div>
          <p>${conf.TEXTS.text_block}</p>
          <div class="button-container">
            <button class="enter-btn" id="agc-alt">${conf.TEXTS.btn_alt}</button>
          </div>
        </div>`;
      popup.style.display = 'flex'; blurOn();
      document.getElementById('agc-alt').addEventListener('click', function(){
        window.location.href = conf.REDIRECT || 'https://www.google.com';
      });
    };

    // Decide by country
    const decide = (cc) => {
      if (!cc) return false;
      cc = cc.toUpperCase();
      if (conf.BLOCKED.includes(cc)) { renderBlocked(); return true; }
      if (conf.POPUP.includes(cc))   { renderPopup();  return true; }
      return false;
    };

    // 1) Immediate country via Cloudflare header (if present)
    if (conf.PHP_COUNTRY && decide(conf.PHP_COUNTRY)) return;

    // 2) Fallback to /cdn-cgi/trace (Cloudflare)
    fetch('/cdn-cgi/trace', {cache:'no-store'})
      .then(r => r.text())
      .then(txt => {
        const m = txt.match(/loc=([A-Z]{2})/);
        const cc = m ? m[1] : '';
        if (!decide(cc)) { /* Not in either list => do nothing */ }
      })
      .catch(() => { /* silent */ });

  } catch(e){ /* silent */ }
})();
</script>
            <?php
        }, 1000);
    }
}

new AGC_Plugin();
