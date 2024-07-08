# VK Term Color

```
composer require vektor-inc/vk-term-color

```

## Usage

```
use VektorInc\VK_Term_Color\VkTermColor;

// カラーピッカー使用時
$vk_term_color = VkTermColor::get_instance();
VkTermColor::init(); 


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

```
use VektorInc\VK_Term_Color\VkTermColor;

global $post;
$args = array(
	'taxonomy' => 'area', // 対象のタクソノミーを指定
);
if ( class_exists( VkTermColor::class ) && method_exists( VkTermColor::class, 'get_post_single_term_info' ) ) {
	$term_info = VkTermColor::get_post_single_term_info( $post, $args );
	echo '<pre style="text-align:left">';
	print_r( $term_info );
	echo '</pre>';
}
```

## PHPUnit
```
composer install
npx wp-env start
npm run phpunit
```

## Change log

0.7.1
* 背景色に合わせて 動的にテキスト色を決める get_dynamic_text_color() を追加

0.7.0
* ブロックなどで使いやすいようにタームの情報を配列で返す get_post_single_term_info() を追加
