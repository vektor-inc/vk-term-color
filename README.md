# VK Term Color

```
composer require vektor-inc/vk-term-color
```

## Usage

```
use VektorInc\VK_Term_Color\VkTermColor;

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