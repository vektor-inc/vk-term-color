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

* カテゴリーなどの分類（タクソノミー）を取得できない状態になると、ラベルを表示しているページが真っ白になる不具合を修正
* 0.7.3 で変更したカテゴリーラベルの文字色（黒/白）の判定方法を、0.7.2 までの方法に戻す
* カテゴリーの色に色コード以外の値（未設定のnullや配列など）が入っていても、PHPの警告が出たり表示が止まったりしないよう文字色の自動判定処理に入力確認を追加

0.7.3
* ターム色の保存処理に、対象タームを編集する権限があるかどうかの確認（current_user_can）を追加
* カテゴリーの背景色に対して自動で選ばれる文字色（黒/白）の判定方法を、コントラスト比を実際に計算して選ぶ方式に改善。読みにくい組み合わせが残っていた中間的な明るさの背景色にも対応

0.7.2
* 投稿のカテゴリーにて背景色に合わせて動的にテキスト色を決める get_dynamic_text_color() を適用
* Composer 経由で配布した際、開発用ファイルが vendor/ に含まれないよう .gitattributes を追加
* ターム色の保存時検証が動くよう、register_meta() の sanitize_callback を連想配列キーで登録する。init の途中で読み込まれた場合もその場で登録する
* ターム色の16進数検証で、末尾に改行を付けた値も誤って許可されてしまう不備を修正。表示箇所のエスケープも強化

0.7.1
* 背景色に合わせて 動的にテキスト色を決める get_dynamic_text_color() を追加

0.7.0
* ブロックなどで使いやすいようにタームの情報を配列で返す get_post_single_term_info() を追加
