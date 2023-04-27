# VK Term Color

```
composer require vektor-inc/vk-term-color
```

## Usage

```
use VektorInc\VK_Term_Color\VkTermColor;

// カラーピッカー使用時のみ
$vk_term_color = VkTermColor::get_instance();
$vk_term_color->init( 'text_domain' ); 


// 表示用のHTMLを取得する
$args = array(
	'outer_element'      => 'div',
	'outer_class'        => '',
	'single_element'     => '',
	'single_class'       => '',
	'single_inner_class' => 'btn btn-sm',
	'link'               => true,
	'color'              => true,
	'taxonomy'           => '', // 表示したいカスタム分類を指定 例） 'category' や 'post_tag'
	'gap'                => '0.5rem',
	'separator'          => '',
);
echo VkTermColor::get_post_terms_html( '', $args );
```